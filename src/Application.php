<?php

namespace FSStats;

use Aws\Api\DateTimeResult;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\Sts\StsClient;
use GuzzleHttp\Psr7\Stream;
use Redis;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class Application
{
    private S3Client $s3client;
    private OutputInterface $output;
    private Redis $redis;
    private RedisIncrementor $incrementor;
    private BloomConfig $bloom;

    private const FILE_SIZE_THRESHOLD = 131072; // 128 * 1024

    public function __construct()
    {
        $this->output = new ConsoleOutput();

        $this->initS3Client();

        $this->redis = new Redis();
        $this->incrementor = new RedisIncrementor($this->redis);
        $this->bloom = new BloomConfig($this->redis);
    }

    private function init(): void
    {
        $this->redis->connect($_ENV['REDIS_HOST'] ?? '172.17.0.2', $_ENV['REDIS_PORT'] ?? 6379);

        if (!$this->redis->ping()) {
            $this->output->writeln('<error>Failed to connect to Redis</error>');
            throw new \Exception('Failed to connect to Redis');
        }
        $this->output->writeln('Connected to Redis');

        $this->incrementor->init();
        $this->bloom->reserve();
    }

    public function run(): void
    {
        $this->output->writeln('Start...');

        $this->init();

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

        $this->redis->close();
        $this->output->writeln('Done');

        $result = $this->incrementor->getResult();
        $this->output->writeln('Result: ');
        $this->output->writeln('Count below 128KB: ' . $result->getCountBelow());
        $this->output->writeln('Count equal or more 128KB: ' . $result->getCountEqualOrMore());
        $this->output->writeln('Size below 128KB: ' . $result->getResultBelow());
        $this->output->writeln('Size equal or more 128KB: ' . $result->getResultEqualOrMore());
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

        /** @var DateTimeResult $tokenExpiration */
        $tokenExpiration = $result['Credentials']['Expiration'];

        $this->output->writeln('');
        $this->output->writeln('----------');
        $this->output->writeln('Assumed role info: ');
        $this->output->writeln('Current SessionToken: ' . substr($result['Credentials']['SessionToken'], 0, 10) . '...');
        $this->output->writeln('Expiration: ' . $tokenExpiration->format('Y-m-d H:i:s'));
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
            if (!isset($data[1], $data[3], $data[4], $data[5])) {
                continue;
            }

            // Bucket, Key, VersionId, IsLatest, IsDeleteMarker, Size
            if ($data[3] != 'true' || $data[4] != 'false' || $data[5] === '') {
                continue;
            }

            $key = $data[1];
            if ($this->bloom->exists($key)) {
                continue;
            }

            $size = (int)$data[5];

            $this->incrementor->add($size, self::FILE_SIZE_THRESHOLD);
            $this->bloom->add($key);
        }

        fclose($stream);
    }
}
