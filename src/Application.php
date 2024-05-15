<?php

namespace FSStats;

use Aws\Api\DateTimeResult;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\Sts\StsClient;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use GuzzleHttp\Psr7\Stream;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class Application
{
    private S3Client $s3client;
    private Client $elasticClient;
    private OutputInterface $output;
    private DateTimeResult $tokenExpiration;
    private const TOKEN_EXPIRATION_THRESHOLD = 20; // in minutes

    public function __construct()
    {
        $this->elasticClient = ClientBuilder::create()
            ->setHosts([
                $_ENV['ELASTICSEARCH_HOST']
            ])
            // https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/connecting.html
            ->setElasticCloudId($_ENV['ELASTICSEARCH_CLOUD_ID'] ?? '')
            ->setApiKey($_ENV['ELASTICSEARCH_API_KEY'] ?? '')
            ->build();
        $this->output = new ConsoleOutput();

        $this->initS3Client();
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

            $this->output->writeln('[' . $date . '] Process links');
            $this->processGzips($gzipUrls);
        }

        $this->output->writeln('Done');
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

    private function initS3Client(): void
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
        $this->output->write('Total: ' . $max . ' | Processed: ');
        foreach ($gzipUrls as $index => $gzipUrl) {
            if ($this->isNeededToUpdateToken()) {
                $this->initS3Client();
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
                $this->initS3Client();
                $this->output->writeln('Failed to open stream. Retry...');
            }
        } while (++$attempt < 3);

        stream_filter_append($stream, 'zlib.inflate', STREAM_FILTER_READ, [
            'window' => 32,
        ]);

        while (
            ($data = fgetcsv($stream, 1000)) !== false
        ) {
            // Bucket, Key, VersionId, IsLatest, IsDeleteMarker, Size

            $this->elasticClient->index([
                'index' => $_ENV['INDEX_NAME'],
                'id' => 'fs-stats-' . time() . '-' . uniqid() . '-' . rand(0, 9999),
                'body' => [
                    'key' => $data[1],
                    'version' => $data[2],
                    'isLatest' => $data[3] == 'true',
                    'isDeleteMarker' => $data[4] == 'true',
                    'size' => $data[5] === '' ? 0 : (int)$data[5],
                ],
            ]);
        }
        fclose($stream);
    }
}
