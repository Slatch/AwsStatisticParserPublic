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
    private RedisChecker $checker;

    private const FILE_SIZE_THRESHOLD = 131072; // 128 * 1024

    public function __construct()
    {
        $this->output = new ConsoleOutput();

        $this->initS3Client();

        $this->redis = new Redis();
        $this->checker = new RedisChecker($this->redis);
    }

    private function init(): void
    {
        $this->output->writeln('Connecting to Redis...');
        $this->redis->connect($_ENV['REDIS_HOST'] ?? '172.17.0.2', $_ENV['REDIS_PORT'] ?? 6379);

        if (!$this->redis->ping()) {
            $this->output->writeln('<error>Failed to connect to Redis</error>');
            throw new \Exception('Failed to connect to Redis');
        }
        $this->output->writeln('Connected to Redis');

        $this->checker->init();
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
            if ($this->checker->hasDate($date)) {
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
            $this->checker->writeDate($date);
        }

        $this->redis->close();
        $this->output->writeln('Done');

        $result = $this->checker->getResult();
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
        $this->output->write('Total: ' . $max . ' | Processed: ');
        foreach ($gzipUrls as $index => $gzipUrl) {
            if ($this->checker->hasUrl($gzipUrl)) {
                $this->output->writeln('URL "' . $gzipUrl . '" Already processed. Skip');
                continue;
            }
            $this->processGzipUrl($gzipUrl);
            $this->output->write(($index + 1) . ' ');
            $this->checker->writeUrl($gzipUrl);
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

        $storage = [];
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

            $storage[md5($data[1])] = (int)$data[5];

            if (++$iterator % 10 === 0) {
                $this->processStorage($storage);
                $storage = [];
            }
        }

        fclose($stream);

        if (!empty($storage)) {
            $this->processStorage($storage);
        }
    }

    private function processStorage(array $storage): void
    {
        $result = $this->redis->sMisMember('myKey', ...array_keys($storage));

        $remaining = $this->getRemaining($storage, array_filter($result));

        $arrayAbove128 = array_filter($remaining, function ($size) {
            return $size >= self::FILE_SIZE_THRESHOLD;
        });
        $arrayBelow128 = array_filter($remaining, function ($size) {
            return $size < self::FILE_SIZE_THRESHOLD;
        });

        $sumAbove128 = array_sum($arrayAbove128);
        $sumBelow128 = array_sum($arrayBelow128);

        $this->redis->sAdd('myKey', ...array_keys($remaining));

        $this->redis->incrBy(RedisChecker::KEY_RESULT_EQUAL_OR_MORE, $sumAbove128);
        $this->redis->incrBy(RedisChecker::KEY_COUNT_EQUAL_OR_MORE, count($arrayAbove128));

        $this->redis->incrBy(RedisChecker::KEY_RESULT_EQUAL_OR_MORE, $sumBelow128);
        $this->redis->incrBy(RedisChecker::KEY_COUNT_BELOW, count($arrayBelow128));
    }

    private function getRemaining(array $storage, array $existingKeys): array
    {
        $values = array_intersect_key(array_keys($storage), array_flip(array_keys($existingKeys)));

        return array_diff_key($storage, array_flip($values));
    }
}
