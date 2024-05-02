<?php

namespace FSStats;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\Sts\StsClient;
use Doctrine\ORM\EntityManager;
use FSStats\Db\EntityManagerBuilder;
use FSStats\Db\Orm\LastProceeded;
use FSStats\Db\Orm\Stats;
use GuzzleHttp\Psr7\Stream;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class Application
{
    private S3Client $client;
    private OutputInterface $output;
    private EntityManager $entityManager;
    private ProgressBar $progressBar;

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
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $_ENV['REGION_READ'],
            'credentials' =>  [
                'key'    => $result['Credentials']['AccessKeyId'],
                'secret' => $result['Credentials']['SecretAccessKey'],
                'token'  => $result['Credentials']['SessionToken']
            ]
        ]);

        $this->output = new ConsoleOutput();
        $this->progressBar = new ProgressBar($this->output);
        $this->entityManager = (new EntityManagerBuilder())->get();
    }

    public function run(): void
    {
        $options = getopt('', [
            'dates::',
        ]);
        $date = $options['dates'] ?? $_ENV['DATES'];
        $dates = explode(',', $date);

        foreach ($dates as $date) {
            try {
                $result = $this->client->getObject([
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

            $this->output->writeln(sprintf('[%s] Progress:', $date));
            $gzipUrls = $this->filterGzips($gzipUrls);
            if (empty($gzipUrls)) {
                $this->output->writeln(sprintf(' > Skipped %s', $date));
                continue;
            }
            $this->processGzips($gzipUrls);
        }

        $this->output->writeln('Done');

        while (true) {
            sleep(1);
        }
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
        $this->client->registerStreamWrapperV2();

        $this->progressBar->setMaxSteps(count($gzipUrls));
        $this->progressBar->setProgress(0);

        foreach ($gzipUrls as $gzipUrl) {
            $this->processGzipUrl($gzipUrl);
            $this->progressBar->advance();
        }

        $this->output->writeln('');
    }

    private function processGzipUrl(string $gzipUrl): void
    {
        if (($stream = fopen($gzipUrl, 'r')) === false) {
            throw new \Exception('Unable to open file: ' . $gzipUrl);
        }

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
