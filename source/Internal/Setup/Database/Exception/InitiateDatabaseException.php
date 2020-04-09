<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Setup\Database\Exception;

use Monolog\Logger;
use \Exception;
use \Throwable;

/**
 * Class InitiateDatabaseException
 *
 * @package OxidEsales\EshopCommunity\Internal\Setup\Database\Exception
 */
class InitiateDatabaseException extends Exception
{

    /** @var int */
    protected $logLevel = Logger::INFO;

    /** @var array */
    protected $metaData = [];

    public function __construct($message = '', $logLevel = Logger::INFO, $code = 0, Throwable $previous = null, $metaData = [])
    {
        $this->logLevel = $logLevel;
        $this->metaData = $metaData;
        parent::__construct($message, $code, $previous);
    }

    public function getLogLevel(): int
    {
        return $this->logLevel;
    }

    public function getMetaData(): array
    {
        return $this->metaData;
    }
}
