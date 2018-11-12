<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseMigrationsBundle\Command;

use BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory;
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

    /** @var \Couchbase\Cluster */
    private $cluster;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var string */
    private $bucketName;

    /**
     * @param \BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory $clusterFactory
     * @param string $username
     * @param string $password
     * @param string $bucketName
     */
    public function __construct(
        ClusterFactory $clusterFactory,
        string $username,
        string $password,
        string $bucketName
    ) {
        $this->cluster = $clusterFactory->getCluster();
        $this->username = $username;
        $this->password = $password;

        parent::__construct();
        $this->bucketName = $bucketName;
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    protected function configure()
    {
        $this
            ->setName('couchbase:migrations:remove-bucket')
            ->setDescription('Remove a bucket.')
            ->addArgument(static::ARGUMENT_BUCKET_NAME, InputArgument::OPTIONAL, 'Name of the bucket your want to remove.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $bucketName = $this->bucketName;
        if (null !== $input->getArgument(static::ARGUMENT_BUCKET_NAME)) {
            $bucketName = $input->getArgument(static::ARGUMENT_BUCKET_NAME);
        }

        try {
            $this->cluster->openBucket($bucketName);

            $clusterManager = $this->cluster->manager($this->username, $this->password);
            $clusterManager->removeBucket($bucketName);

            $io->success(sprintf('Bucket \'%s\' removed.', $bucketName));
        } catch (\Throwable $e) {

            $io->warning(sprintf('Bucket \'%s\' does not exist', $bucketName));
        }
    }
}
