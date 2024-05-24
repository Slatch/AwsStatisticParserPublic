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
    private DateTimeResult $tokenExpiration;
    private Redis $redis;

    private const KEY_RESULT_BELOW = 'result_below';
    private const KEY_COUNT_BELOW = 'count_below';
    private const KEY_RESULT_EQUAL_OR_MORE = 'result_equal_or_more';
    private const KEY_COUNT_EQUAL_OR_MORE = 'count_equal_or_more';
    private const FILE_SIZE_THRESHOLD = 131072;

    public function __construct()
    {
        $this->output = new ConsoleOutput();

        $this->initS3Client();

        $this->redis = new Redis();
    }

    private function fillRedis(): void
    {
        if (!$this->redis->exists(self::KEY_RESULT_BELOW)) {
            $this->redis->set(self::KEY_RESULT_BELOW, 0);
        }
        if (!$this->redis->exists(self::KEY_COUNT_BELOW)) {
            $this->redis->set(self::KEY_COUNT_BELOW, 0);
        }
        if (!$this->redis->exists(self::KEY_RESULT_EQUAL_OR_MORE)) {
            $this->redis->set(self::KEY_RESULT_EQUAL_OR_MORE, 0);
        }
        if (!$this->redis->exists(self::KEY_COUNT_EQUAL_OR_MORE)) {
            $this->redis->set(self::KEY_COUNT_EQUAL_OR_MORE, 0);
        }
    }

    public function run(): void
    {
        $this->output->writeln('Start...');

        $this->redis->connect($_ENV['REDIS_HOST'] ?? '172.17.0.2', $_ENV['REDIS_PORT'] ?? 6379);

        if (!$this->redis->ping()) {
            $this->output->writeln('<error>Failed to connect to Redis</error>');
            return;
        }
        $this->output->writeln('Connected to Redis');

        $this->fillRedis();

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

        $this->output->writeln('Result: ');
        $this->output->writeln('Count below 128KB: ' . $this->redis->get(self::KEY_COUNT_BELOW));
        $this->output->writeln('Total size below 128KB: ' . $this->redis->get(self::KEY_RESULT_BELOW));
        $this->output->writeln('Count equal or more 128KB: ' . $this->redis->get(self::KEY_COUNT_EQUAL_OR_MORE));
        $this->output->writeln('Total size equal or more 128KB: ' . $this->redis->get(self::KEY_RESULT_EQUAL_OR_MORE));
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

            $key = md5($data[1]);
            if ($this->redis->exists($key)) {
                continue;
            }

            $size = (int)$data[5];
            if ($size >= self::FILE_SIZE_THRESHOLD) {
                $this->redis->incrBy('result_equal_or_more', $size);
                $this->redis->incr('count_equal_or_more');
            } else {
                $this->redis->incrBy('result_below', $size);
                $this->redis->incr('count_below');
            }

            $this->redis->set($key, 1);
        }

        fclose($stream);
    }
}
