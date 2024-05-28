<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseMigrationsBundle\Command;

use BowlOfSoup\CouchbaseMigrationsBundle\Factory\BucketFactory;
use BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory;
use BowlOfSoup\CouchbaseMigrationsBundle\Repository\BucketRepository;
use Couchbase\Cluster;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Creates a bucket.
 */
class CreateBucketCommand extends Command
{
    const ARGUMENT_BUCKET_NAME = 'bucket';
    const OPTION_CREATE_INDEX = 'index';

    private ClusterFactory $clusterFactory;

    private Cluster $cluster;

    private string $username;

    private string $password;

    private string $bucketName;

    private array $bucketOptions = [
        'bucketType' => 'couchbase',
        'ramQuotaMB' => 128,
        'saslPassword' => '',
        'flushEnabled' => true,
        'replicaNumber' => 1,
        'replicaIndex' => false
    ];

    public function __construct(
        ClusterFactory $clusterFactory,
        string $username,
        string $password,
        string $bucketName
    ) {
        $this->clusterFactory = $clusterFactory;
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
            ->setName('couchbase:migrations:create-bucket')
            ->setDescription('Creates a bucket.')
            ->addArgument(static::ARGUMENT_BUCKET_NAME, InputArgument::OPTIONAL, 'Name of the bucket your want to create.')
            ->addOption(static::OPTION_CREATE_INDEX, null, InputOption::VALUE_NONE, 'Also create a primary index.');
    }

    /**
     * @throws \BowlOfSoup\CouchbaseMigrationsBundle\Exception\BucketNoAccessException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $bucketName = $this->bucketName;
        if (null !== $input->getArgument(static::ARGUMENT_BUCKET_NAME)) {
            $bucketName = $input->getArgument(static::ARGUMENT_BUCKET_NAME);
        }

        try {
            $this->cluster->bucket($bucketName);

            $io->warning(sprintf('Bucket \'%s\' already exists', $bucketName));
        } catch (\Throwable $e) {
            $clusterManager = $this->cluster->buckets();
            $clusterManager->createBucket($bucketName, $this->bucketOptions);

            // We wait until Couchbase actually created the bucket.
            sleep(2);

            if ($input->getOption(static::OPTION_CREATE_INDEX)) {
                $this->createPrimaryIndex($bucketName);
            }

            $io->success(sprintf('Bucket \'%s\' created.', $bucketName));
        }

        return self::SUCCESS;
    }

    private function createPrimaryIndex(string $bucketName): void
    {
        $bucketFactory = new BucketFactory($this->clusterFactory, $bucketName);
        $bucketRepository = new BucketRepository($bucketFactory, $this->clusterFactory->getCluster());

        $bucketRepository->query(
            sprintf('CREATE PRIMARY INDEX ON `%s` USING GSI', $bucketName)
        );

        // We wait until Couchbase processed the index.
        sleep(1);
    }
}
