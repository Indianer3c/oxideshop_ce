<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Migration;

use OxidEsales\DoctrineMigrationWrapper\Migrations;
use OxidEsales\DoctrineMigrationWrapper\MigrationsBuilder;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class MigrationExecutor
 *
 * @package OxidEsales\EshopCommunity\Internal\Framework\Migration
 */
class MigrationExecutor implements MigrationExecutorInterface
{

    /** @var ContextInterface */
    protected $context;

    /**
     * @param ContextInterface $context
     */
    public function __construct(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * @param ConsoleOutput|null $output
     */
    public function execute(ConsoleOutput $output = null): void
    {
        $migrations = $this->createMigrations();
        $migrations->setOutput($output);
        $migrations->execute(Migrations::MIGRATE_COMMAND);
    }

    /**
     * @return Migrations
     */
    private function createMigrations(): Migrations
    {
        $migrationsBuilder = new MigrationsBuilder();

        return $migrationsBuilder->build($this->context->getFacts());
    }
}
