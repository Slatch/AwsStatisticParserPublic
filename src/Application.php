<?php

namespace FSStats;

use Aws\Api\DateTimeResult;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\Sts\StsClient;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use FSStats\Db\EntityManagerBuilder;
use FSStats\Db\Orm\LastProceeded;
use FSStats\Db\Orm\Stats;
use GuzzleHttp\Psr7\Stream;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class Application
{
    private S3Client $client;
    private OutputInterface $output;
    private EntityManager $entityManager;
    private DateTimeResult $tokenExpiration;
    private const TOKEN_EXPIRATION_THRESHOLD = 20; // in minutes

    public function __construct()
    {
        $this->output = new ConsoleOutput();
        $this->entityManager = (new EntityManagerBuilder())->get();

        $this->initClient();
        $this->initDb();
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
            $this->output->writeln('[' . $date . '] Get symlink.txt');
            try {
                $result = $this->client->getObject([
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

            $this->output->writeln(sprintf('[%s] Filter links:', $date));
            $gzipUrls = $this->filterGzips($gzipUrls);
            if (empty($gzipUrls)) {
                $this->output->writeln(sprintf(' > Skipped %s', $date));
                continue;
            }

            $this->output->writeln('[' . $date . '] Process links');
            $this->processGzips($gzipUrls);
        }

        $this->output->writeln('Done');

        while ($_ENV['TERMINATE'] ?? true) {
            sleep(1);
        }
    }

    private function isNeededToUpdateToken(): bool
    {
        $origin = new \DateTime();
        $target = $this->tokenExpiration;

        $interval = $origin->diff($target);
        return
            $interval->h === 0 &&
            $interval->i <= self::TOKEN_EXPIRATION_THRESHOLD;
    }

    private function initClient(): void
    {
        $stsClient = new StsClient([
            'region' => $_ENV['REGION_READ'],
            'version' => 'latest',
        ]);
        $result = $stsClient->AssumeRole([
            'RoleArn' => $_ENV['ARN_ROLE_READ'],
            'RoleSessionName' => 's3-access-temporary-read-' . time(),
        ]);
        $this->tokenExpiration = $result['Credentials']['Expiration'];

        $this->output->writeln('');
        $this->output->writeln('----------');
        $this->output->writeln('Assumed role info: ');
        $this->output->writeln('Current SessionToken: ' . substr($result['Credentials']['SessionToken'], 0, 10) . '...');
        $this->output->writeln('Expiration: ' . $this->tokenExpiration->format('Y-m-d H:i:s'));
        $this->output->writeln('----------');
        $this->output->writeln('');

        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $_ENV['REGION_READ'],
            'credentials' =>  [
                'key' => $result['Credentials']['AccessKeyId'],
                'secret' => $result['Credentials']['SecretAccessKey'],
                'token' => $result['Credentials']['SessionToken']
            ]
        ]);
        $this->client->registerStreamWrapperV2();
    }

    private function initDb(): void
    {
        $tool = new SchemaTool($this->entityManager);
        $classes = [
            $this->entityManager->getClassMetadata(Stats::class),
            $this->entityManager->getClassMetadata(LastProceeded::class),
        ];
        $tool->dropSchema($classes);
        $tool->createSchema($classes);
    }

    private function filterGzips(array $gzipUrls): array
    {
        /** @var string[] $proccededGzips */
        $proccededGzips = array_map(function(LastProceeded $lastProceeded) {
            return $lastProceeded->getGzip();
        }, $this->entityManager->getRepository(LastProceeded::class)->findAll());

        return array_diff($gzipUrls, $proccededGzips);
    }

    private function processGzips(array $gzipUrls): void
    {
        $max = count($gzipUrls);
        $this->output->write('Total: ' . $max . ' | Processed: ');
        foreach ($gzipUrls as $index => $gzipUrl) {
            if ($this->isNeededToUpdateToken()) {
                $this->initClient();
            }
            $this->processGzipUrl($gzipUrl);
            $this->output->write(($index + 1) . ' ');
        }

        $this->output->writeln(PHP_EOL);
    }

    private function processGzipUrl(string $gzipUrl): void
    {
        $attempt = 0;
        do {
            if (($stream = fopen($gzipUrl, 'r')) === false) {
                $this->initClient();
                $this->output->writeln('Failed to open stream. Retry...');
            }
        } while (++$attempt < 3);

        stream_filter_append($stream, 'zlib.inflate', STREAM_FILTER_READ, [
            'window' => 32,
        ]);

        $counter = 0;
        while (
            ($data = fgetcsv($stream, 1000)) !== false
        ) {
            // Bucket, Key, VersionId, IsLatest, IsDeleteMarker, Size
            /*if (strtolower($data[4]) !== 'false') { // isDeleteMarker
                continue;
            }
            if (strtolower($data[3]) !== 'true') { // isLatest
                continue;
            }*/

            $stat = new Stats();
            $stat->setKey($data[1]);
            $stat->setVersion($data[2]);
            $stat->setIsLatest($data[3] == 'true');
            $stat->setIsDeleteMarker($data[4] == 'true');
            if (!empty($data[5]) || $data[5] === '0') {
                $stat->setSize((int)$data[5]);
            }

            $this->entityManager->persist($stat);

            if ((++$counter % $_ENV['BATCH_SIZE']) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        fclose($stream);

        $lastProceeded = new LastProceeded();
        $lastProceeded->setGzip($gzipUrl);
        $this->entityManager->persist($lastProceeded);

        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
