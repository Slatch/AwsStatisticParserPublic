<?php

namespace FSStats;

use Aws\Api\DateTimeResult;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\Sts\StsClient;
use FSStats\Model\LastDate;
use FSStats\Model\LastUrl;
use FSStats\Model\Usage0;
use FSStats\Model\Usage1;
use FSStats\Model\Usage10;
use FSStats\Model\Usage11;
use FSStats\Model\Usage2;
use FSStats\Model\Usage3;
use FSStats\Model\Usage4;
use FSStats\Model\Usage5;
use FSStats\Model\Usage6;
use FSStats\Model\Usage7;
use FSStats\Model\Usage8;
use FSStats\Model\Usage9;
use GuzzleHttp\Psr7\Stream;
use Illuminate\Database\Capsule\Manager;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class Application
{
    private S3Client $s3client;
    private OutputInterface $output;

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
        $attempt = 0;
        do {
            if (($stream = fopen($gzipUrl, 'r')) === false) {
                $this->initS3Client();
                $this->output->writeln('Failed to open stream. Retry...');
            }
        } while (++$attempt < 3);

        stream_filter_append($stream, 'zlib.inflate', STREAM_FILTER_READ, [
            'window' => 32,
        ]);

        $storage = $this->initStorage();
        $iterator = 0;

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

            $this->fillStorage(md5($data[1]), (int)$data[5], $storage);

            if (++$iterator % ($_ENV['BATCH_SIZE'] ?? 1000) === 0) {
                $this->processStorage($storage);
                $storage = $this->initStorage();
            }
        }

        fclose($stream);

        $this->processStorage($storage);
    }

    private function processStorage(array $storage): void
    {
        foreach ($storage as $index => $values) {
            switch ($index) {
                case 0:
                    if (!empty($values)) {
                        Usage0::insert($values);
                    }
                    break;
                case 1:
                    if (!empty($values)) {
                        Usage1::insert($values);
                    }
                    break;
                case 2:
                    if (!empty($values)) {
                        Usage2::insert($values);
                    }
                    break;
                case 3:
                    if (!empty($values)) {
                        Usage3::insert($values);
                    }
                    break;
                case 4:
                    if (!empty($values)) {
                        Usage4::insert($values);
                    }
                    break;
                case 5:
                    if (!empty($values)) {
                        Usage5::insert($values);
                    }
                    break;
                case 6:
                    if (!empty($values)) {
                        Usage6::insert($values);
                    }
                    break;
                case 7:
                    if (!empty($values)) {
                        Usage7::insert($values);
                    }
                    break;
                case 8:
                    if (!empty($values)) {
                        Usage8::insert($values);
                    }
                    break;
                case 9:
                    if (!empty($values)) {
                        Usage9::insert($values);
                    }
                    break;
                case 10:
                    if (!empty($values)) {
                        Usage10::insert($values);
                    }
                    break;
                case 11:
                    if (!empty($values)) {
                        Usage11::insert($values);
                    }
                    break;
            }
        }

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
        ]);
        $capsule->bootEloquent();
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

    private function initStorage(): array
    {
        return [
            0 => [],
            1 => [],
            2 => [],
            3 => [],
            4 => [],
            5 => [],
            6 => [],
            7 => [],
            8 => [],
            9 => [],
            10 => [],
            11 => [],
        ];
    }

    private function fillStorage(string $key, int $size, array &$storage): void
    {
        if ($size < 500) {
            $storage[0][] = [
                'key' => $key,
                'size' => $size,
            ];
            return;
        }
        if ($size < 4000) {
            $storage[1][] = [
                'key' => $key,
                'size' => $size,
            ];
            return;
        }
        if ($size < 8000) {
            $storage[2][] = [
                'key' => $key,
                'size' => $size,
            ];
            return;
        }
        if ($size < 16000) {
            $storage[3][] = [
                'key' => $key,
                'size' => $size,
            ];
            return;
        }
        if ($size < 34000) {
            $storage[4][] = [
                'key' => $key,
                'size' => $size,
            ];
            return;
        }
        if ($size < 56000) {
            $storage[5][] = [
                'key' => $key,
                'size' => $size,
            ];
            return;
        }
        if ($size < 90000) {
            $storage[6][] = [
                'key' => $key,
                'size' => $size,
            ];
            return;
        }
        if ($size < 131072) {
            $storage[7][] = [
                'key' => $key,
                'size' => $size,
            ];
            return;
        }
        if ($size < 210000) {
            $storage[8][] = [
                'key' => $key,
                'size' => $size,
            ];
            return;
        }
        if ($size < 350000) {
            $storage[9][] = [
                'key' => $key,
                'size' => $size,
            ];
            return;
        }
        if ($size < 1000000) {
            $storage[10][] = [
                'key' => $key,
                'size' => $size,
            ];
            return;
        }
        $storage[11][] = [
            'key' => $key,
            'size' => $size,
        ];
    }
}
