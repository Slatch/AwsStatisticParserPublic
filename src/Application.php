<?php

namespace FSStats;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\Sts\StsClient;
use GuzzleHttp\Psr7\Stream;

final class Application
{
    private S3Client $clientRead;
    private S3Client $clientWrite;
    private MessageParser $messageParser;
    private MessageFormatter $messageFormatter;
    private Output $output;
    private ?ProceededValuesDto $proceededValues = null;

    private const TARGET_SIZE = 131072; // 128 * 1024

    public function __construct()
    {
        $stsClient = new StsClient([
            'region' => $_ENV['REGION_READ'],
            'version' => 'latest',
        ]);
        $result = $stsClient->AssumeRole([
            'RoleArn' => $_ENV['ARN_ROLE_READ'],
            'RoleSessionName' => 's3-access-temporary-read',
        ]);
        $this->clientRead = new S3Client([
            'version' => 'latest',
            'region' => $_ENV['REGION_READ'],
            'credentials' =>  [
                'key'    => $result['Credentials']['AccessKeyId'],
                'secret' => $result['Credentials']['SecretAccessKey'],
                'token'  => $result['Credentials']['SessionToken']
            ]
        ]);

        $stsClient = new StsClient([
            'region' => $_ENV['REGION_WRITE'],
            'version' => 'latest',
        ]);
        $result = $stsClient->AssumeRole([
            'RoleArn' => $_ENV['ARN_ROLE_WRITE'],
            'RoleSessionName' => 's3-access-temporary-write',
        ]);
        $this->clientWrite = new S3Client([
            'version' => 'latest',
            'region' => $_ENV['REGION_WRITE'],
            'credentials' =>  [
                'key'    => $result['Credentials']['AccessKeyId'],
                'secret' => $result['Credentials']['SecretAccessKey'],
                'token'  => $result['Credentials']['SessionToken']
            ]
        ]);

        $this->messageParser = new MessageParser();
        $this->messageFormatter = new MessageFormatter();
        $this->output = new Output();
    }

    public function run(): void
    {
        $options = getopt('', [
            'dates::',
        ]);
        $date = $options['dates'] ?? $_ENV['DATES'];
        $dates = explode(',', $date);

        foreach ($dates as $date) {
            $this->initValues($date);

            try {
                $result = $this->clientRead->getObject([
                    'Bucket' => $_ENV['BUCKET_NAME_READ'],
                    'Key' => $_ENV['STATISTIC_FOLDER_PATH'] . 'hive/dt=' . $date . '-01-00/symlink.txt',
                ]);
            } catch (S3Exception $e) {
                $this->output->writeln('<error>' . $e->getMessage() . '</error>');
                return;
            }

            /** @var Stream $body */
            $body = $result['Body'];
            $body->rewind();

            $gzipUrls = explode(PHP_EOL, $body->getContents());

            $body->close();

            $gzipUrls = $this->filterGzips($gzipUrls);

            $this->processGzips($date, $gzipUrls);
        }
    }

    private function initValues(string $date): void
    {
        try {
            $result = $this->clientWrite->getObject([
                'Bucket' => $_ENV['BUCKET_NAME_WRITE'],
                'Key' => $_ENV['RESULT_FOLDER_PATH'] . Config::RESPONSE_FOLDER . $date . '.txt',
            ]);
            $this->proceededValues = $this->messageParser->parse($result['Body']->getContents());
            $this->output->writeln(sprintf('[%s] Initialized with values from s3', $date));
        } catch (S3Exception $e) {
            $this->proceededValues = null;
            $this->output->writeln(sprintf('[%s] Initialized with default values', $date));
        }
    }

    private function filterGzips(array $gzipUrls): array
    {
        if ($this->isFirstScriptRun()) {
            return $gzipUrls;
        }

        $lastProceededFileIndex = array_search($this->proceededValues->getLastFile(), $gzipUrls);
        if ($lastProceededFileIndex === false) {
            return $gzipUrls;
        }

        $gzipUrls = array_slice($gzipUrls, $lastProceededFileIndex + 1);
        if (empty($gzipUrls)) {
            $this->output->writeln('No new data found');
        }

        return $gzipUrls;
    }

    private function processGzips(string $date, array $gzipUrls): void
    {
        $this->clientRead->registerStreamWrapperV2();

        $urlsCount = count($gzipUrls);
        foreach ($gzipUrls as $index => $gzipUrl) {
            $this->processGzipUrl($date, $gzipUrl);
            $this->output->writeln(
                sprintf(
                    '[%s] Processed %d of %d (%d%%)',
                    $date,
                    $index + 1,
                    $urlsCount,
                    ($index + 1) / $urlsCount * 100
                )
            );
        }

        $this->output->writeln('');
    }

    private function processGzipUrl(string $date, string $gzipUrl): void
    {
        $totalBytes = 0;
        $bytesAboveTarget = 0;

        $totalFiles = 0;
        $filesAboveTarget = 0;

        if (($stream = fopen($gzipUrl, 'r')) === false) {
            throw new \Exception('Unable to open file: ' . $gzipUrl);
        }

        stream_filter_append($stream, 'zlib.inflate', STREAM_FILTER_READ, [
            'window' => 32,
        ]);

        while (
            ($data = fgetcsv($stream, 1000)) !== false
        ) {
            if (strtolower($data[4]) !== 'false') { // isDeleteMarker
                continue;
            }
            $size = (int)$data[5];

            $totalBytes += $size;
            $totalFiles++;

            if ($size > self::TARGET_SIZE) {
                $bytesAboveTarget += $size;
                $filesAboveTarget++;
            }
        }
        fclose($stream);

        $bytesBelowTarget = $totalBytes - $bytesAboveTarget;
        $filesBelowTarget = $totalFiles - $filesAboveTarget;
        if ($this->isFirstScriptRun()) {
            $dto = new ProceededValuesDto(
                $totalBytes, $bytesAboveTarget, $bytesBelowTarget,
                $totalFiles, $filesAboveTarget, $filesBelowTarget,
                $date
            );
        } else {
            $dto = new ProceededValuesDto(
                $this->proceededValues->getTotalBytes() + $totalBytes,
                $this->proceededValues->getBytesAboveTarget() + $bytesAboveTarget,
                $this->proceededValues->getBytesBelowTarget() + $bytesBelowTarget,
                $this->proceededValues->getTotalFiles() + $totalFiles,
                $this->proceededValues->getFilesAboveTarget() + $filesAboveTarget,
                $this->proceededValues->getFilesBelowTarget() + $filesBelowTarget,
                $gzipUrl
            );
        }

        $this->writeToFile($date, $dto);
        $this->proceededValues = $dto;

        $this->output->writeln(sprintf('> Proceeded %d elements' . PHP_EOL, $totalFiles));
    }

    private function writeToFile(string $date, ProceededValuesDto $dto): void
    {
        $this->clientWrite->putObject([
            'Bucket' => $_ENV['BUCKET_NAME_WRITE'],
            'Key' => $_ENV['RESULT_FOLDER_PATH'] . Config::RESPONSE_FOLDER . $date . '.txt',
            'Body' => $this->messageFormatter->formatMessage(
                $dto->getTotalBytes(),
                $dto->getBytesAboveTarget(),
                $dto->getBytesBelowTarget(),
                $dto->getTotalFiles(),
                $dto->getFilesAboveTarget(),
                $dto->getFilesBelowTarget(),
                $dto->getLastFile()
            ),
        ]);
    }

    private function isFirstScriptRun(): bool
    {
        return $this->proceededValues === null;
    }
}
