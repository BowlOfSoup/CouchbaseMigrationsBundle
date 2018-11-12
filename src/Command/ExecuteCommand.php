<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Command;

use BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory;
use BowlOfSoup\CouchbaseMigrationsBundle\Factory\MigrationFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

/**
 * Executes a single Couchbase migration.
 */
class ExecuteCommand extends Command
{
    const INPUT_VERSION = 'version';
    const OPTION_MIGRATE_DOWN = 'down';

    /** @var string */
    private $migrationsDirectory;

    /** @var \BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory */
    private $clusterFactory;

    /**
     * @param string $projectDirectory
     * @param \BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory $clusterFactory
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(
        string $projectDirectory,
        ClusterFactory $clusterFactory
    ) {
        $this->migrationsDirectory = $projectDirectory . GenerateCommand::DIRECTORY_MIGRATIONS;
        $this->clusterFactory = $clusterFactory;

        parent::__construct();
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    protected function configure()
    {
        $this
            ->setName('couchbase:migrations:execute')
            ->setDescription('Executes a single Couchbase migration.')
            ->addArgument(static::INPUT_VERSION, InputArgument::REQUIRED, 'Which version do you want to execute?')
            ->addOption(static::OPTION_MIGRATE_DOWN, null, InputOption::VALUE_NONE, 'Undo migration.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     * @throws \ReflectionException
     * @throws \BowlOfSoup\CouchbaseMigrationsBundle\Exception\BucketNoAccessException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $fileName = 'Version' . $input->getArgument(static::INPUT_VERSION) . '.php';

        $finder = new Finder();
        $finder->files()->in($this->migrationsDirectory)->depth('== 0')->name($fileName);

        if (!$finder->hasResults()) {
            $io->error(sprintf('Migration: %s does not exist in %s.', $fileName, $this->migrationsDirectory));

            return;
        }

        $migrationFactory = new MigrationFactory($this->clusterFactory);

        $iterator = $finder->getIterator();
        $iterator->rewind();

        // Use the first result of found files.
        $migration = $migrationFactory->createByFile($iterator->current());
        $migration->selectBucket();

        if ($input->getOption(static::OPTION_MIGRATE_DOWN)) {
            $migration->down();
        } else {
            $migration->up();
        }

        $io->success('Migration done.');
    }
}
