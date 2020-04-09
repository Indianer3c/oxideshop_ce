<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Setup\Database;

interface DbServiceInterface
{
    /**
     * @param array $parameters
     */
    public function initiateDb(array $parameters): void;
}
