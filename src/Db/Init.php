<?php

namespace FSStats\Db;

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;

class Init
{
    private EntityManagerBuilder $builder;

    public function __construct()
    {
        $this->builder = new EntityManagerBuilder();
    }

    public function init(): void
    {
        $entityManager = $this->builder->get();

        ConsoleRunner::run(
            new SingleManagerProvider($entityManager)
        );
    }
}
