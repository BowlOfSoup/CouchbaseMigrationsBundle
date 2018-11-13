<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseMigrationsBundle\Command;

use BowlOfSoup\CouchbaseMigrationsBundle\Factory\BucketFactory;
use BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory;
use BowlOfSoup\CouchbaseMigrationsBundle\Repository\BucketRepository;
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

    /** @var \BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory */
    private $clusterFactory;

    /** @var \Couchbase\Cluster */
    private $cluster;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var string */
    private $bucketName;

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
     * @param string $bucketName
     */
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
    protected function configure()
    {
        $this
            ->setName('couchbase:migrations:create-bucket')
            ->setDescription('Creates a bucket.')
            ->addArgument(static::ARGUMENT_BUCKET_NAME, InputArgument::OPTIONAL, 'Name of the bucket your want to create.')
            ->addOption(static::OPTION_CREATE_INDEX, null, InputOption::VALUE_NONE, 'Also create a primary index.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @throws \BowlOfSoup\CouchbaseMigrationsBundle\Exception\BucketNoAccessException
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

            $io->warning(sprintf('Bucket \'%s\' already exists', $bucketName));
        } catch (\Throwable $e) {
            $clusterManager = $this->cluster->manager($this->username, $this->password);
            $clusterManager->createBucket($bucketName, $this->bucketOptions);

            // We wait until Couchbase actually created the bucket.
            sleep(2);

            if ($input->getOption(static::OPTION_CREATE_INDEX)) {
                $this->createPrimaryIndex($bucketName);
            }

            $io->success(sprintf('Bucket \'%s\' created.', $bucketName));
        }
    }

    /**
     * @param string $bucketName
     *
     * @throws \BowlOfSoup\CouchbaseMigrationsBundle\Exception\BucketNoAccessException
     */
    private function createPrimaryIndex(string $bucketName)
    {
        $bucketFactory = new BucketFactory($this->clusterFactory, $bucketName);
        $bucketRepository = new BucketRepository($bucketFactory);

        $bucketRepository->query(
            sprintf('CREATE PRIMARY INDEX ON `%s` USING GSI', $bucketName)
        );

        // We wait until Couchbase processed the index.
        sleep(1);
    }
}
