<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Setup\Database;

use OxidEsales\EshopCommunity\Internal\Framework\Migration\MigrationExecutorInterface;
use OxidEsales\EshopCommunity\Internal\Setup\Database\DbService;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;

class DbServiceTest extends TestCase
{

    use ContainerTrait;

    public function testInitiateDb(): void
    {
        $params = [
            'dbHost' => 'localhost',
            'dbPort' => '3306',
            'dbUser' => 'oxid',
            'dbPwd' => 'oxid',
            'dbName' => 'db_service_test_1',
            'adminUser' => 'admin',
            'adminPwd' => 'admin',
            'sendTechnicalInformationToOxid' => true,
            'checkForUpdates' => true,
            'countryLang' => 'en',
            'shopLang' => 'en',
            'sendShopDataToOxid' => true
        ];

        $dbService = $this->getDbService();
        $dbService->initiateDb($params);
    }

    /**
     * @return DbService
     */
    private function getDbService(): DbService
    {
        $context = $this->get(ContextInterface::class);
        $shopAdapter = $this->get(ShopAdapterInterface::class);
        $migrationExecutor = $this->get(MigrationExecutorInterface::class);

        return new DbService($context, $shopAdapter, $migrationExecutor);
    }
}
