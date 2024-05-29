<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle;

use BowlOfSoup\CouchbaseMigrationsBundle\DependencyInjection\CouchbaseMigrationsExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class CouchbaseMigrationsBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new CouchbaseMigrationsExtension();
    }
}
