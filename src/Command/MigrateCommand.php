<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Command;

use BowlOfSoup\CouchbaseMigrationsBundle\Exception\BucketNoAccessException;
use BowlOfSoup\CouchbaseMigrationsBundle\Factory\BucketFactory;
use BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory;
use Couchbase\Bucket;
use Couchbase\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class MigrateCommand extends Command
{
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

    protected function configure()
    {
        $this
            ->setName('couchbase:migrations:migrate')
            ->setDescription('Executes Couchbase migrations.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @throws \BowlOfSoup\CouchbaseMigrationsBundle\Exception\BucketNoAccessException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();
        $finder->files()->in($this->migrationsDirectory);

        $migrationsBucket = $this->getMigrationsBucket();
        $doneVersions = $this->getVersions($migrationsBucket);

        foreach ($finder as $file) {
            $version = str_replace('.php', '', $file->getFilename());

            if (!in_array($version, $doneVersions)) {
                require_once $file->getPathname();

                /** @var \BowlOfSoup\CouchbaseMigrationsBundle\Migration\AbstractMigration $migration */
                $migration = new $version($this->clusterFactory);
                $migration->up();

                array_push($doneVersions, $version);
                $migrationsBucket->upsert(static::DOCUMENT_VERSIONS, $doneVersions);
            }
        }
    }

    /**
     * @return \Couchbase\Bucket
     *
     * @throws \BowlOfSoup\CouchbaseMigrationsBundle\Exception\BucketNoAccessException
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
            $migrationsBucket->upsert(static::DOCUMENT_VERSIONS, array());

            return [];
        }
    }
}
