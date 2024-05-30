<?php

namespace FSStats;

use Aws\Api\DateTimeResult;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\Sts\StsClient;
use FSStats\Model\LastDate;
use FSStats\Model\LastUrl;
use FSStats\Model\Usage;
use GuzzleHttp\Psr7\Stream;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class Application
{
    private S3Client $s3client;
    private OutputInterface $output;
    private Connection $connection;

    private const TARGET = 131072;

    public function __construct()
    {
        $this->output = new ConsoleOutput();

        $this->initS3Client();

        $this->initDB();
    }

    public function run(): void
    {
        $this->output->writeln('Start...');

        $options = getopt('', [
            'dates::',
        ]);
        $date = $options['dates'] ?? $_ENV['DATES'];
        $dates = explode(',', $date);

        foreach ($dates as $date) {
            if ($this->isDateExists($date)) {
                $this->output->writeln('[' . $date . '] Already processed. Skip');
                continue;
            }
            $this->output->writeln('[' . $date . '] Get symlink.txt from s3');
            try {
                $result = $this->s3client->getObject([
                    'Bucket' => $_ENV['BUCKET_NAME_READ'],
                    'Key' => $_ENV['STATISTIC_FOLDER_PATH'] . 'hive/dt=' . $date . '-01-00/symlink.txt',
                ]);
            } catch (S3Exception $e) {
                $this->output->writeln('<error>' . $e->getMessage() . '</error>');
                return;
            }

            $this->output->writeln('[' . $date . '] Get body');
            /** @var Stream $body */
            $body = $result['Body'];
            $body->rewind();

            $gzipUrls = explode(PHP_EOL, $body->getContents());

            $body->close();
            unset($body, $result);

            $this->output->writeln('[' . $date . '] Process links');
            $this->processGzips($gzipUrls);

            $this->writeDate($date);
        }

        $this->output->writeln('Done');
    }

    private function initS3Client(): void
    {
        $stsClient = new StsClient([
            'region' => $_ENV['REGION_READ'],
            'version' => 'latest',
        ]);
        $this->output->writeln('Assuming role...');
        $result = $stsClient->AssumeRole([
            'RoleArn' => $_ENV['ARN_ROLE_READ'],
            'RoleSessionName' => 's3-access-temporary-read-' . time(),
        ]);

        /** @var DateTimeResult $tokenExpiration */
        $tokenExpiration = $result['Credentials']['Expiration'];

        $this->output->writeln('Assumed role token expiration: ' . $tokenExpiration->format('Y-m-d H:i:s'));

        $this->s3client = new S3Client([
            'version' => 'latest',
            'region' => $_ENV['REGION_READ'],
            'credentials' =>  [
                'key' => $result['Credentials']['AccessKeyId'],
                'secret' => $result['Credentials']['SecretAccessKey'],
                'token' => $result['Credentials']['SessionToken']
            ]
        ]);
        $this->s3client->registerStreamWrapperV2();
    }

    private function processGzips(array $gzipUrls): void
    {
        $max = count($gzipUrls);
        $this->output->writeln('Total: ' . $max);
        foreach ($gzipUrls as $index => $gzipUrl) {
            if ($this->isUrlExists($gzipUrl)) {
                continue;
            }
            $this->processGzipUrl($gzipUrl);
            $this->output->writeln(($index + 1) . '/' . $max);
            $this->writeUrl($gzipUrl);
        }

        $this->output->writeln(PHP_EOL);
    }

    private function processGzipUrl(string $gzipUrl): void
    {
        $localArchiveStream = fopen(sys_get_temp_dir() . '/raw_archive.csv.gz', 'wb');

        $attempt = 0;
        do {
            if (($gzipS3Stream = fopen($gzipUrl, 'r')) !== false) {
                break;
            }
            $this->output->writeln('Failed to open stream. Retry...');
            $this->initS3Client();
        } while (++$attempt < 3);

        stream_copy_to_stream($gzipS3Stream, $localArchiveStream);

        fclose($gzipS3Stream);
        fclose($localArchiveStream);

        copy('compress.zlib://' . sys_get_temp_dir() . '/raw_archive.csv.gz', sys_get_temp_dir() . '/raw_file.csv');

        unlink(sys_get_temp_dir() . '/raw_archive.csv.gz');

        $rawCsvStream = fopen(sys_get_temp_dir() . '/raw_file.csv', 'r');
        $filteredCsvStream = fopen(sys_get_temp_dir() . '/filtered_file.csv', 'w');

        while (
            ($data = fgetcsv($rawCsvStream, 1000)) !== false
        ) {
            if (!isset($data[1], $data[3], $data[4], $data[5])) {
                continue;
            }

            // $data[0], $data[1], $data[2], $data[3], $data[4], $data[5]
            // Bucket, Key, VersionId, IsLatest, IsDeleteMarker, Size
            if ($data[3] != 'true' || $data[4] != 'false' || $data[5] === '') {
                continue;
            }

            fputcsv($filteredCsvStream, [md5($data[1]), (int)$data[5]]);
        }

        fclose($filteredCsvStream);

        fclose($rawCsvStream);
        unlink(sys_get_temp_dir() . '/raw_file.csv');

        $this->connection->statement(
            'LOAD DATA LOCAL INFILE "' . sys_get_temp_dir() . '/filtered_file.csv"
                INTO TABLE `stat_parser`.`' . Usage::TABLE_NAME . '`
                FIELDS TERMINATED BY ","
                LINES TERMINATED BY "\n";'
        );

        unlink(sys_get_temp_dir() . '/filtered_file.csv');
    }

    private function initDB()
    {
        $capsule = new Manager();
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'database' => $_ENV['DB_NAME'] ?? 'stat_parser',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? 'my-secret-pw',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'options' => [
                \PDO::MYSQL_ATTR_LOCAL_INFILE => true,
            ],
        ]);
        $capsule->bootEloquent();

        $this->connection = $capsule->getConnection('default');

        $this->connection->statement("
CREATE TABLE IF NOT EXISTS `stat_parser`.`" . Usage::TABLE_NAME . "` (`key` varchar(32)  NOT NULL, `size` int(16) NOT NULL);
        ");
        $this->connection->statement("
CREATE TABLE IF NOT EXISTS `stat_parser`.`" . LastDate::TABLE_NAME . "` (`id` int(5) unsigned NOT NULL auto_increment, `date` varchar(10)  NOT NULL default '', PRIMARY KEY  (`id`));
        ");
        $this->connection->statement("
CREATE TABLE IF NOT EXISTS `stat_parser`.`" . LastUrl::TABLE_NAME . "` (`id` int(5) unsigned NOT NULL auto_increment, `url` varchar(250)  NOT NULL default '', PRIMARY KEY  (`id`));
        ");
        $this->connection->statement("SET GLOBAL unique_checks=0");
        $this->connection->statement("SET GLOBAL foreign_key_checks=0");
        $this->connection->statement("SET GLOBAL local_infile=1");
    }

    private function isDateExists(string $date): bool
    {
        return LastDate::query()->where('date', $date)->exists();
    }

    private function writeDate(string $date): void
    {
        LastDate::query()->insert([
            'date' => $date,
        ]);
    }

    private function isUrlExists(string $url): bool
    {
        return LastUrl::query()->where('url', $url)->exists();
    }

    private function writeUrl(string $url): void
    {
        LastUrl::query()->insert([
            'url' => $url,
        ]);
    }

    private function debug()
    {
        copy('compress.zlib://data/0074ee9b-5ca8-458f-b8ef-0ac83038c72d.csv.gz', sys_get_temp_dir() . '/raw_file.csv');

        $stream = fopen(sys_get_temp_dir() . '/raw_file.csv', 'r');
        $writeStream = fopen(sys_get_temp_dir() . '/filtered_file.csv', 'w');
        while (
            ($data = fgetcsv($stream, 1000)) !== false
        ) {
            if (!isset($data[1], $data[3], $data[4], $data[5])) {
                continue;
            }
            // $data[0], $data[1], $data[2], $data[3], $data[4], $data[5]
            // Bucket, Key, VersionId, IsLatest, IsDeleteMarker, Size
            if ($data[3] != 'true' || $data[4] != 'false' || $data[5] === '') {
                continue;
            }

            fputcsv($writeStream, [md5($data[1]), (int)$data[5]]);
        }
        fclose($writeStream);

        fclose($stream);
        unlink(sys_get_temp_dir() . '/raw_file.csv');





        try {
            $this->connection->statement(
                'LOAD DATA LOCAL INFILE "' . sys_get_temp_dir() . '/filtered_file.csv"
                INTO TABLE `stat_parser`.`usage`
                FIELDS TERMINATED BY ","
                LINES TERMINATED BY "\n";'
            );
        } catch (\Throwable $e) {
            $this->output->writeln('<error>' . $e->getMessage() . '</error>');
            $this->output->writeln(sys_get_temp_dir() . '/filtered_file.csv');
            // docker run -e BATCH_SIZE="10" -e DB_HOST=172.17.0.3 -it --name p02-29 --rm statistic-aggregator:v2 php app.php --dates=2024-02-29
        }
        return;
    }
}
