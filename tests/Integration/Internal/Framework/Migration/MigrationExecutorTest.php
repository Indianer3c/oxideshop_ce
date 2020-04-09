<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Migration;

use OxidEsales\EshopCommunity\Internal\Framework\Migration\MigrationExecutor;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;

class MigrationExecutorTest extends TestCase
{

    use ContainerTrait;

    public function testCreateMigrations(): void
    {
        $migrationExecutor = $this->getMigrationExecutor();
        $migrationExecutor->execute();
    }

    /**
     * @return MigrationExecutor
     */
    private function getMigrationExecutor(): MigrationExecutor
    {
        $context = $this->get(ContextInterface::class);

        return new MigrationExecutor($context);
    }
}
