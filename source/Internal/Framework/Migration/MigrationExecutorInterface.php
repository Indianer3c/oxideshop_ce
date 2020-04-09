<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Migration;

use Symfony\Component\Console\Output\ConsoleOutput;

interface MigrationExecutorInterface
{

    /**
     * @param ConsoleOutput|null $output
     */
    public function execute(ConsoleOutput $output = null): void;
}
