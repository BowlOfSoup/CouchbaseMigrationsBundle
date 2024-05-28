<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseMigrationsBundle\Command;

use BowlOfSoup\CouchbaseMigrationsBundle\Factory\BucketFactory;
use BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory;
use Couchbase\Exception\CouchbaseException;
use Couchbase\Exception\DocumentExistsException;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\Exception\TimeoutException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Flushes all data in a Couchbase bucket, except the migrations document.
 */
class FlushDataCommand extends Command
{
    const INPUT_BUCKET = 'bucket';

    protected ClusterFactory $clusterFactory;

    public function __construct(
        ClusterFactory $clusterFactory
    ) {
        $this->clusterFactory = $clusterFactory;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('couchbase:migrations:flush-data')
            ->setDescription('Flushes all data in a Couchbase bucket, except the migrations document.')
            ->addArgument(static::INPUT_BUCKET, InputArgument::OPTIONAL, 'For which bucket you want the data to be flushed?');
    }

    /**
     * @throws CouchbaseException
     * @throws DocumentExistsException
     * @throws DocumentNotFoundException
     * @throws TimeoutException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $bucketName = $input->getArgument(static::INPUT_BUCKET) ?? $this->clusterFactory->getDefaultBucketName();

        if (!$this->userConfirmation($bucketName, $input, $output)) {
            return self::FAILURE;
        }

        $bucketFactory = new BucketFactory($this->clusterFactory, $bucketName);
        $bucket = $bucketFactory->getBucket();

        $migrationsVersionsDocumentContent = $bucket->defaultCollection()->get(MigrateCommand::DOCUMENT_VERSIONS);

        $bucketManager = $this->clusterFactory->getCluster()->buckets();
        $bucketManager->flush($bucketName);
        $bucket->defaultCollection()->insert(MigrateCommand::DOCUMENT_VERSIONS, $migrationsVersionsDocumentContent->content());

        $io = new SymfonyStyle($input, $output);
        $io->success(sprintf('Flushed all data for: %s.', $bucketName));

        return self::SUCCESS;
    }

    private function userConfirmation(string $bucketName, InputInterface $input, OutputInterface $output): bool
    {
        $io = new SymfonyStyle($input, $output);
        $io->caution([
            sprintf('This command will flush all data from bucket %s, except the migration data.', $bucketName),
        ]);

        $question = new ConfirmationQuestion(
            '<question>Are you sure you want to proceed ?</question> (y/N): ',
            false
        );
        $question->setMaxAttempts(2);
        $helper = $this->getHelper('question');

        if (!$helper->ask($input, $output, $question)) {
            return false;
        }

        return true;
    }
}
