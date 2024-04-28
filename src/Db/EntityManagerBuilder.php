<?php

namespace FSStats\Db;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

class EntityManagerBuilder
{
    public function get()
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [__DIR__],
            isDevMode: filter_var($_ENV['DEV_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => __DIR__ . '/../../data/db.sqlite',
        ], $config);

        return new EntityManager($connection, $config);
    }
}
