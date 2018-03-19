<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Command;

use BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory;
use BowlOfSoup\CouchbaseMigrationsBundle\Factory\MigrationFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ExecuteCommand extends Command
{
    const INPUT_VERSION = 'version';

    /** @var string */
    private $migrationsDirectory;

    /** @var \BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory */
    private $clusterFactory;

    /**
     * @param string $projectDirectory
     * @param \BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory $clusterFactory
     */
    public function __construct(
        string $projectDirectory,
        ClusterFactory $clusterFactory
    ) {
        $this->migrationsDirectory = $projectDirectory . GenerateCommand::DIRECTORY_MIGRATIONS;
        $this->clusterFactory = $clusterFactory;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('couchbase:migrations:execute')
            ->setDescription('Executes a single Couchbase migration.')
            ->addArgument(static::INPUT_VERSION, InputArgument::REQUIRED, 'Which version do you want to execute?');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileName = 'Version' . $input->getArgument(static::INPUT_VERSION).'.php';
        $finder = new Finder();
        $finder->files()->in($this->migrationsDirectory)->name($fileName);

        if(count($finder) !== 1) {
            throw new \InvalidArgumentException(sprintf('Migration: %s does not exist in %s.', $fileName, $this->migrationsDirectory));
        }

        $migrationFactory = new MigrationFactory($this->clusterFactory);
        $iterator = $finder->getIterator();
        $iterator->rewind();
        $migration = $migrationFactory->createByFile($iterator->current());
        $migration->up();
    }
}
