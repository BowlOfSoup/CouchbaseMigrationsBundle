<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Command;

use BowlOfSoup\CouchbaseMigrationsBundle\Exception\BucketNoAccessException;
use BowlOfSoup\CouchbaseMigrationsBundle\Factory\BucketFactory;
use BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory;
use BowlOfSoup\CouchbaseMigrationsBundle\Factory\MigrationFactory;
use Couchbase\Bucket;
use Couchbase\Exception\CouchbaseException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

/**
 * Execute all (pending) Couchbase migrations.
 */
class MigrateCommand extends Command
{
    const OPTION_NO_VERBOSE = 'no-verbose';
    const DOCUMENT_VERSIONS = 'migrations::versions';

    private string $migrationsDirectory;

    private string $migrationsBucket;

    private ClusterFactory $clusterFactory;

    /**
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
    protected function configure(): void
    {
        $this
            ->setName('couchbase:migrations:migrate')
            ->setDescription('Execute all (pending) Couchbase migrations.')
            ->addOption(static::OPTION_NO_VERBOSE, null, InputOption::VALUE_NONE, 'Don\'t output any visuals.');
    }

    /**
     * @throws \BowlOfSoup\CouchbaseMigrationsBundle\Exception\BucketNoAccessException
     * @throws \InvalidArgumentException
     * @throws \LogicException
     * @throws \ReflectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
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

            return self::FAILURE;
        }

        $migrationsBucket = $this->getMigrationsBucket();

        $doneVersions = $this->getVersions($migrationsBucket);
        $migrationFactory = new MigrationFactory($this->clusterFactory);

        foreach ($finder as $file) {
            $migration = $migrationFactory->createByFile($file);

            if (in_array(get_class($migration), $doneVersions)) {
                continue;
            }

            $migration->selectBucket();
            $migration->up();

            array_push($doneVersions, get_class($migration));
            $migrationsBucket->defaultCollection()->upsert(static::DOCUMENT_VERSIONS, $doneVersions);

            if (!$input->getOption(static::OPTION_NO_VERBOSE)) {
                $io->writeln(sprintf('Executed migration: <info>%s</info>.', get_class($migration)));
            }
        }

        if (!$input->getOption(static::OPTION_NO_VERBOSE)) {
            $io->success('Migrations done.');
        }

        return self::SUCCESS;
    }

    /**
     * @throws \BowlOfSoup\CouchbaseMigrationsBundle\Exception\BucketNoAccessException
     */
    private function getMigrationsBucket(): Bucket
    {
        return (new BucketFactory($this->clusterFactory, $this->migrationsBucket))->getBucket();
    }

    private function getVersions(Bucket $migrationsBucket): array
    {
        try {
            $result = $migrationsBucket->defaultCollection()->get(static::DOCUMENT_VERSIONS);

            return $result->content();
        } catch (CouchbaseException $e) {
            // This occurs when the document containing the versions can't be found. Create an empty document.
            $migrationsBucket->defaultCollection()->upsert(static::DOCUMENT_VERSIONS, []);

            return [];
        }
    }
}
