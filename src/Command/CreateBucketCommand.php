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
 * Creates a bucket.
 */
class CreateBucketCommand extends Command
{
    const ARGUMENT_BUCKET_NAME = 'bucket';

    /** @var \Couchbase\Cluster */
    private $cluster;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var array */
    private $bucketOptions = [
        'bucketType' => 'couchbase',
        'ramQuotaMB' => 128,
        'saslPassword' => '',
        'flushEnabled' => true,
        'replicaNumber' => 1,
        'replicaIndex' => false
    ];

    /**
     * @param \BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory $clusterFactory
     * @param string $username
     * @param string $password
     */
    public function __construct(
        ClusterFactory $clusterFactory,
        string $username,
        string $password
    ) {
        $this->cluster = $clusterFactory->getCluster();
        $this->username = $username;
        $this->password = $password;

        parent::__construct();
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    protected function configure()
    {
        $this
            ->setName('couchbase:migrations:create-bucket')
            ->setDescription('Creates a bucket.')
            ->addArgument(static::ARGUMENT_BUCKET_NAME, InputArgument::REQUIRED, 'Name of the bucket your want to create.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $bucketName = $input->getArgument(static::ARGUMENT_BUCKET_NAME);

        try {
            $this->cluster->openBucket($bucketName);

            $io->warning(sprintf('Bucket \'%s\' already exists', $bucketName));
        } catch (\Throwable $e) {
            $clusterManager = $this->cluster->manager($this->username, $this->password);
            $clusterManager->createBucket($bucketName, $this->bucketOptions);

            $io->success(sprintf('Bucket \'%s\' created.', $bucketName));
        }
    }
}
