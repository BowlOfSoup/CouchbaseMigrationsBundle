<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Factory;

use BowlOfSoup\CouchbaseMigrationsBundle\Model\ConnectionSettings;
use Couchbase\Cluster;
use Couchbase\ClusterOptions;

class ClusterFactory
{
    private ConnectionSettings $connectionSettings;

    private string $defaultBucketName;

    private ?Cluster $cluster = null;

    /**
     * @param \BowlOfSoup\CouchbaseMigrationsBundle\Model\ConnectionSettings $connectionSettings
     * @param string $defaultBucketName
     */
    public function __construct(
        ConnectionSettings $connectionSettings,
        string $defaultBucketName
    ) {
        $this->connectionSettings = $connectionSettings;
        $this->defaultBucketName = $defaultBucketName;
    }

    public function getCluster(): Cluster
    {
        if (null !== $this->cluster) {
            return $this->cluster;
        }

        $host = (null !== $this->connectionSettings->getPort())
            ? $this->connectionSettings->getHost() . ':' . $this->connectionSettings->getPort()
            : $this->connectionSettings->getHost();

        $options = new ClusterOptions();
        $options->credentials($this->connectionSettings->getUser(), $this->connectionSettings->getPassword());

        $this->cluster = new Cluster('couchbase://' . $host, $options);

        return $this->cluster;
    }

    public function getDefaultBucketName(): string
    {
        return $this->defaultBucketName;
    }
}
