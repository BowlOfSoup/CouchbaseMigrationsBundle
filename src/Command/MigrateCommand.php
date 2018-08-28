<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Command;

use BowlOfSoup\CouchbaseMigrationsBundle\Exception\BucketNoAccessException;
use BowlOfSoup\CouchbaseMigrationsBundle\Factory\BucketFactory;
use BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory;
use BowlOfSoup\CouchbaseMigrationsBundle\Factory\MigrationFactory;
use Couchbase\Bucket;
use Couchbase\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

class MigrateCommand extends Command
{
    const OPTION_NO_VERBOSE = 'no-verbose';
    const DOCUMENT_VERSIONS = 'migrations::versions';

    /** @var string */
    private $migrationsDirectory;

    /** @var string */
    private $migrationsBucket;

    /** @var \BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory */
    private $clusterFactory;

    /**
     * @param string $projectDirectory
     * @param string $migrationsBucket
     * @param \BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory $clusterFactory
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(
        string $projectDirectory,
        string $migrationsBucket,
        ClusterFactory $clusterFactory
    ) {
        $this->migrationsDirectory = $projectDirectory . GenerateCommand::DIRECTORY_MIGRATIONS;
        $this->migrationsBucket = $migrationsBucket;
        $this->clusterFactory = $clusterFactory;

        parent::__construct();
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('couchbase:migrations:migrate')
            ->setDescription('Executes Couchbase migrations.')
            ->addOption(static::OPTION_NO_VERBOSE, null, InputOption::VALUE_NONE, 'Don\'t output any visuals.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @throws \BowlOfSoup\CouchbaseMigrationsBundle\Exception\BucketNoAccessException
     * @throws \InvalidArgumentException
     * @throws \LogicException
     * @throws \ReflectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $finder = new Finder();
        $finder
            ->files()
            ->in($this->migrationsDirectory)
            ->depth('== 0')
            ->name('Version*.php')
            ->sortByName();

        if (!$finder->hasResults()) {
            if (!$input->getOption(static::OPTION_NO_VERBOSE)) {
                $io->warning('Nothing to execute.');
            }

            return;
        }

        $migrationsBucket = $this->getMigrationsBucket();
        $doneVersions = $this->getVersions($migrationsBucket);
        $migrationFactory = new MigrationFactory($this->clusterFactory);

        foreach ($finder as $file) {
            $migration = $migrationFactory->createByFile($file);

            if (in_array(get_class($migration), $doneVersions)) {
                continue;
            }

            try {
                $migration->up();
            } catch (\Throwable $t) {
                $migration->selectBucket();
                $migration->up();
            }

            array_push($doneVersions, get_class($migration));
            $migrationsBucket->upsert(static::DOCUMENT_VERSIONS, $doneVersions);

            if (!$input->getOption(static::OPTION_NO_VERBOSE)) {
                $io->writeln(sprintf('Executed migration: <info>%s</info>.', get_class($migration)));
            }
        }

        if (!$input->getOption(static::OPTION_NO_VERBOSE)) {
            $io->success('Migrations done.');
        }
    }

    /**
     * @throws \BowlOfSoup\CouchbaseMigrationsBundle\Exception\BucketNoAccessException
     *
     * @return \Couchbase\Bucket
     */
    private function getMigrationsBucket()
    {
        try {
            return (new BucketFactory($this->clusterFactory, $this->migrationsBucket))->getBucket();
        } catch (BucketNoAccessException $e) {
            throw new BucketNoAccessException(sprintf(BucketNoAccessException::BUCKET_MIGRATIONS_CANNOT_BE_ACCESSED, $this->migrationsBucket));
        }
    }

    /**
     * @param \Couchbase\Bucket $migrationsBucket
     *
     * @return array
     */
    private function getVersions(Bucket $migrationsBucket)
    {
        try {
            $result = $migrationsBucket->get(static::DOCUMENT_VERSIONS);

            return $result->value;
        } catch (Exception $e) {
            // This occurs when the document containing the versions can't be found. Create an empty document.
            $migrationsBucket->upsert(static::DOCUMENT_VERSIONS, []);

            return [];
        }
    }
}
