<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Command;

use BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->setName('couchbase:migration:execute')
            ->setDescription('Executes a single Couchbase migration.')
            ->addArgument(static::INPUT_VERSION, InputArgument::REQUIRED, 'Which version do you want to execute?');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $className = 'Version' . $input->getArgument(static::INPUT_VERSION);
        $fileName =  $className . '.php';
        $version = $this->migrationsDirectory . '/' . $fileName;

        if (!file_exists($version)) {
            throw new \InvalidArgumentException(sprintf('Migration: %s does not exist.', $fileName));
        }

        require_once $version;

        /** @var \BowlOfSoup\CouchbaseMigrationsBundle\Migration\AbstractMigration $migration */
        $migration = new $className($this->clusterFactory);
        $migration->up();
    }
}
