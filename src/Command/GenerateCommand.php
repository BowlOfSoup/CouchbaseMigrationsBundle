<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate a blank couchbase migration.
 */
class GenerateCommand extends Command
{
    const DIRECTORY_MIGRATIONS = '/CouchbaseMigrations';

    private string $migrationsDirectory;

    public function __construct(
        string $projectDirectory
    ) {
        $this->migrationsDirectory = $projectDirectory . static::DIRECTORY_MIGRATIONS;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('couchbase:migrations:generate')
            ->setDescription('Generate a blank couchbase migration.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $version = date('YmdHis');
        $code = str_replace(['%version%'], [date('YmdHis')], file_get_contents(__DIR__ . '/../Resources/templates/Migration.tmp'));

        if (!file_exists($this->migrationsDirectory)) {
            throw new \InvalidArgumentException(sprintf('Migrations directory "%s" does not exist.', $this->migrationsDirectory));
        }

        $file = '/Version' . $version . '.php';
        file_put_contents($this->migrationsDirectory . $file, $code);

        $output->writeln(PHP_EOL . sprintf('Generated new migration to "<info>%s</info>"', static::DIRECTORY_MIGRATIONS . $file) . PHP_EOL);

        return self::SUCCESS;
    }
}
