<?php

namespace FSStats;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\Sts\StsClient;
use GuzzleHttp\Psr7\Stream;

class BuildResponseApplication
{
    private S3Client $client;
    private MessageParser $messageParser;
    private MessageFormatter $messageFormatter;
    private Output $output;

    public function __construct()
    {
        $stsClient = new StsClient([
            'region' => $_ENV['REGION_WRITE'],
            'version' => 'latest',
        ]);
        $result = $stsClient->AssumeRole([
            'RoleArn' => $_ENV['ARN_ROLE_WRITE'],
            'RoleSessionName' => 's3-access-temporary-write',
        ]);
        $this->client = new S3Client([
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
        try {
            $result = $this->client->ListObjectsV2([
                'Bucket' => $_ENV['BUCKET_NAME_WRITE'],
                'Prefix' => $_ENV['RESULT_FOLDER_PATH'] . Config::RESPONSE_FOLDER,
            ]);
        } catch (S3Exception $e) {
            return;
        }

        $this->processDates($result['Contents']);
    }

    private function processDates(array $contents)
    {
        $totalBytes = 0;
        $bytesAboveTarget = 0;
        $bytesBelowTarget = 0;

        $totalFiles = 0;
        $filesAboveTarget = 0;
        $filesBelowTarget = 0;

        foreach ($contents as $content) {
            $this->output->writeln('Parse ' . $content['Key']);
            $result = $this->client->getObject([
                'Bucket' => $_ENV['BUCKET_NAME_WRITE'],
                'Key' => $content['Key'],
            ]);

            /** @var Stream $body */
            $body = $result['Body'];
            $body->rewind();

            $dto = $this->messageParser->parse($body->getContents());

            $totalBytes += $dto->getTotalBytes();
            $bytesAboveTarget += $dto->getBytesAboveTarget();
            $bytesBelowTarget += $dto->getBytesBelowTarget();

            $totalFiles += $dto->getTotalFiles();
            $filesAboveTarget += $dto->getFilesAboveTarget();
            $filesBelowTarget += $dto->getFilesBelowTarget();
        }

        $this->client->putObject([
            'Bucket' => $_ENV['BUCKET_NAME_WRITE'],
            'Key' => $_ENV['RESULT_FOLDER_PATH'] . Config::RESPONSE_FILE,
            'Body' => $this->messageFormatter->formatMessage(
                $totalBytes,
                $bytesAboveTarget,
                $bytesBelowTarget,
                $totalFiles,
                $filesAboveTarget,
                $filesBelowTarget,
                '-'
            ),
        ]);

        $this->output->writeln('Done');
    }
}
