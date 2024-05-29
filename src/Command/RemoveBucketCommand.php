<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseMigrationsBundle\Command;

use BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory;
use Couchbase\Cluster;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Removes a bucket.
 */
class RemoveBucketCommand extends Command
{
    const ARGUMENT_BUCKET_NAME = 'bucket';

    private Cluster $cluster;

    private string $username;

    private string $password;

    private string $bucketName;

    public function __construct(
        ClusterFactory $clusterFactory,
        string $username,
        string $password,
        string $bucketName
    ) {
        $this->cluster = $clusterFactory->getCluster();
        $this->username = $username;
        $this->password = $password;
        $this->bucketName = $bucketName;

        parent::__construct();
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    protected function configure(): void
    {
        $this
            ->setName('couchbase:migrations:remove-bucket')
            ->setDescription('Remove a bucket.')
            ->addArgument(static::ARGUMENT_BUCKET_NAME, InputArgument::OPTIONAL, 'Name of the bucket your want to remove.');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $bucketName = $this->bucketName;
        if (null !== $input->getArgument(static::ARGUMENT_BUCKET_NAME)) {
            $bucketName = $input->getArgument(static::ARGUMENT_BUCKET_NAME);
        }

        try {
            $this->cluster->bucket($bucketName);

            $clusterManager = $this->cluster->buckets();
            $clusterManager->removeBucket($bucketName);

            $io->success(sprintf('Bucket \'%s\' removed.', $bucketName));

            return self::SUCCESS;
        } catch (\Throwable $e) {

            $io->warning(sprintf('Bucket \'%s\' does not exist', $bucketName));

            return self::FAILURE;
        }
    }
}
