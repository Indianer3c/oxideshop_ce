<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Setup\Database;

use Monolog\Logger;
use OxidEsales\EshopCommunity\Internal\Framework\Migration\MigrationExecutorInterface;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Exception\InitiateDatabaseException;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use PDO;

/**
 * Class DbService
 * initiateDb() must be called in SETUP command
 *
 * @package OxidEsales\EshopCommunity\Internal\Setup\Database
 */
class DbService implements DbServiceInterface
{

    public const COMMUNITY_EDITION = 'CE';
    public const INTERNAL_SETUP_SQL_PATH = 'Internal/Setup/Database/Sql';

    /** @var ContextInterface */
    protected $context;

    /** @var ShopAdapterInterface */
    protected $shopAdapter;

    /** @var MigrationExecutorInterface */
    protected $migrationExecutor;

    /** @var PDO */
    protected $dbConnection = null;

    /** @var string */
    protected $dsn;

    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /** @var string */
    protected $dbName;

    /** @var string */
    protected $adminUsername;

    /** @var string */
    protected $adminPassword;

    /** @var bool */
    protected $sendTechnicalInformationToOxid = true;

    /** @var bool */
    protected $checkForUpdates = false;

    /** @var string */
    protected $countryLang;

    /** @var string */
    protected $shopLang;

    /** @var bool */
    protected $sendShopDataToOxid = false;

    /**
     * @param ContextInterface           $context
     * @param ShopAdapterInterface       $shopAdapter
     * @param MigrationExecutorInterface $migrationExecutor
     */
    public function __construct(
        ContextInterface $context,
        ShopAdapterInterface $shopAdapter,
        MigrationExecutorInterface $migrationExecutor
    ) {
        $this->context = $context;
        $this->shopAdapter = $shopAdapter;
        $this->migrationExecutor = $migrationExecutor;
    }

    /**
     * @param array $parameters
     *
     * @throws InitiateDatabaseException
     */
    public function initiateDb(array $parameters): void
    {
        $this->setParameters($parameters);

        $this->getDatabaseConnection();

        $this->dbConnection->beginTransaction();
        try {
            if (!$this->isDatabaseExist()) {
                $this->createDb();
            }

            $this->isPossibleToCreateAndUseView();

            $this->enterInitialData();

            $this->saveShopSettings();

            $this->writeAdminLoginData();

            $this->dbConnection->commit();
        } catch (\Throwable $exception) {
            $this->dbConnection->rollBack();
            throw new InitiateDatabaseException('Failed: ' . $exception->getMessage(), Logger::ERROR,
                $exception->getCode(), $exception);
        }
    }

    /**
     * @param array $params
     */
    private function setParameters(array $params): void
    {
        $this->dsn = sprintf('mysql:host=%s;port=%s', $params['dbHost'], $params['dbPort']);
        $this->username = $params['dbUser'];
        $this->password = $params['dbPwd'];
        $this->dbName = $params['dbName'];
        $this->adminUsername = $params['adminUser'];
        $this->adminPassword = $params['adminPwd'];

        if ($this->context->getEdition() === $this::COMMUNITY_EDITION) {
            $this->sendTechnicalInformationToOxid = $params['sendTechnicalInformationToOxid'];
        }

        $this->checkForUpdates = $params['checkForUpdates'];
        $this->countryLang = $params['countryLang'];
        $this->shopLang = $params['shopLang'];
        $this->sendShopDataToOxid = $params['sendShopDataToOxid'];
    }

    /**
     * @throws InitiateDatabaseException
     */
    private function getDatabaseConnection(): void
    {
        try {
            $this->dbConnection = new PDO(
                $this->dsn,
                $this->username,
                $this->password,
                [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']
            );
            $this->dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->dbConnection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (\Throwable $exception) {
            throw new InitiateDatabaseException('Failed: Unable to connect to database', Logger::ERROR,
                $exception->getCode(), $exception);
        }
    }

    /**
     * @return string
     */
    private function getDatabaseVersion(): string
    {
        $version = $this->executeSqlQuery("SHOW VARIABLES LIKE 'version'");

        return $version->fetchColumn(1);
    }

    /**
     * @return bool
     */
    private function isDatabaseExist(): bool
    {
        try {
            $this->executeSqlQuery('USE ' . $this->dbName);
        } catch (\Throwable $exception) {
            return false;
        }

        return true;
    }

    private function createDb(): void
    {
        try {
            $this->executeSqlQuery("CREATE DATABASE `$this->dbName` CHARACTER SET utf8 COLLATE utf8_general_ci;");
            $this->executeSqlQuery('USE ' . $this->dbName);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Could not create database');
        }
    }

    private function isPossibleToCreateAndUseView(): void
    {
        try {
            $this->executeSqlQuery('CREATE OR REPLACE VIEW oxviewtest As SELECT 1');
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Could not create view');
        }

        try {
            $this->executeSqlQuery('SELECT * FROM oxviewtest');
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Could not run SELECT statement on view');
        }

        try {
            $this->executeSqlQuery('DROP VIEW oxviewtest');
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Could not drop view');
        }
    }

    /**
     * @param string $query
     *
     * @return mixed
     */
    private function executeSqlQuery(string $query)
    {
        try {
            [$statement] = explode(' ', ltrim($query));

            if (in_array(strtoupper($statement), ['SELECT', 'SHOW'])) {
                return $this->dbConnection->query($query);
            }

            return $this->dbConnection->exec($query);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('SQL query could not be run');
        }
    }

    private function enterInitialData(): void
    {
        $sqlFilePath = $this->context->getCommunityEditionSourcePath() . '/' . $this::INTERNAL_SETUP_SQL_PATH;
        $this->executeSqlQueryFromFile("$sqlFilePath/database_schema.sql");
        $this->executeSqlQueryFromFile("$sqlFilePath/initial_data.sql");

        try {
            $this->migrationExecutor->execute();
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Could not enter initial data');
        }
    }

    /**
     * @param string $sqlFilePath
     */
    private function executeSqlQueryFromFile(string $sqlFilePath): void
    {
        $openedFile = @fopen($sqlFilePath, 'rb');
        if (!$openedFile) {
            throw new \RuntimeException('SQL file can not be opened');
        }

        $query = fread($openedFile, filesize($sqlFilePath));
        fclose($openedFile);

        if (version_compare($this->getDatabaseVersion(), '5') > 0) {
            $this->executeSqlQuery("SET @@session.sql_mode = ''");
        }

        $queries = $this->parseSqlQuery($query);
        foreach ($queries as $query) {
            $this->executeSqlQuery($query);
        }
    }

    /**
     * @param string $query
     *
     * @return array
     */
    private function parseSqlQuery(string $query): array
    {
        $result = [];
        $comment = false;
        $quote = false;
        $thisSQL = '';

        $lines = explode("\n", $query);

        foreach ($lines as $line) {
            $length = strlen($line);

            for ($i = 0; $i < $length; $i++) {
                if (!$quote && ($line[$i] === '#' || ($line[0] === '-' && $line[1] === '-'))) {
                    $comment = true;
                }

                if (!$comment) {
                    $thisSQL .= $line[$i];
                }

                // test if quote on
                if (($line[$i] === '\'' && $line[$i - 1] !== '\\')) {
                    $quote = !$quote;
                }

                if (!$quote && $line[$i] === ';') {
                    $thisSQL = trim($thisSQL);
                    if ($thisSQL) {
                        $thisSQL = str_replace("\r", '', $thisSQL);
                        $result[] = $thisSQL;
                    }
                    $thisSQL = '';
                }
            }

            $comment = false;
            $quote = false;
        }

        return $result;
    }

    private function saveShopSettings(): void
    {
        $baseShopId = $this->context->getDefaultShopId();

        $this->executeSqlQuery("update oxcountry set oxactive = '0'");
        $this->executeSqlPrepareQuery(
            "update oxcountry set oxactive = '1' where oxid = :countryLang",
            [
                [':countryLang' => $this->countryLang]
            ]
        );

        $this->executeSqlQuery("delete from oxconfig where oxvarname = 'blSendTechnicalInformationToOxid'");
        $this->executeSqlQuery("delete from oxconfig where oxvarname = 'blCheckForUpdates'");
        $this->executeSqlQuery("delete from oxconfig where oxvarname = 'sDefaultLang'");
        $this->executeSqlQuery("delete from oxconfig where oxvarname = 'blSendShopDataToOxid'");

        $this->executeSqlPrepareQuery(
            "insert into oxconfig (oxid, oxshopid, oxvarname, oxvartype, oxvarvalue) values (:oxid, :shopId, :name, :type, :value)",
            [
                [
                    'oxid'   => $this->shopAdapter->generateUniqueId(),
                    'shopId' => $baseShopId,
                    'name'   => 'blSendTechnicalInformationToOxid',
                    'type'   => 'bool',
                    'value'  => $this->sendTechnicalInformationToOxid
                ],
                [
                    'oxid'   => $this->shopAdapter->generateUniqueId(),
                    'shopId' => $baseShopId,
                    'name'   => 'blCheckForUpdates',
                    'type'   => 'bool',
                    'value'  => $this->checkForUpdates
                ],
                [
                    'oxid'   => $this->shopAdapter->generateUniqueId(),
                    'shopId' => $baseShopId,
                    'name'   => 'sDefaultLang',
                    'type'   => 'str',
                    'value'  => $this->shopLang
                ],
                [
                    'oxid'   => $this->shopAdapter->generateUniqueId(),
                    'shopId' => $baseShopId,
                    'name'   => 'blSendShopDataToOxid',
                    'type'   => 'bool',
                    'value'  => $this->sendShopDataToOxid
                ]
            ]
        );

        $configParameters = $this->executeSqlQuery("select oxvarname, oxvartype, oxvarvalue from oxconfig where oxvarname='aLanguageParams'");
        if ($configParameters && false !== ($row = $configParameters->fetch())) {
            $languageValuesType = $row['oxvartype'];
            $languageValues = $row['oxvarvalue'];

            if (!is_array(unserialize($languageValues))) {
                throw new \RuntimeException('aLanguageParams can not be type of 
                ' . gettype($row['oxvarvalue']) . ', aLanguageParams must be type of array');
            }
            if (in_array($languageValuesType, ['arr', 'aarr'])) {
                $languageValues = unserialize($languageValues);
            }

            foreach ($languageValues as $key => $lang) {
                $languageValues[$key]['active'] = '0';
            }
            $languageValues[$this->shopLang]['active'] = '1';

            $languageValues = serialize($languageValues);

            $this->executeSqlQuery("delete from oxconfig where oxvarname = 'aLanguageParams'");

            $this->executeSqlPrepareQuery(
                "insert into oxconfig (oxid, oxshopid, oxvarname, oxvartype, oxvarvalue) values (:oxid, :shopId, :name, :type, :value)",
                [
                    [
                        'oxid'   => $this->shopAdapter->generateUniqueId(),
                        'shopId' => $baseShopId,
                        'name'   => 'aLanguageParams',
                        'type'   => 'aarr',
                        'value'  => $languageValues
                    ]
                ]
            );
        }
    }

    /**
     * @param string $query
     *
     * @param array  $params
     */
    private function executeSqlPrepareQuery(string $query, array $params): void
    {
        try {
            $prepareStatement = $this->dbConnection->prepare($query);

            foreach ($params as $param) {
                $prepareStatement->execute($param);
            }
        } catch (\Throwable $exception) {
            throw new \RuntimeException('SQL query could not be run');
        }
    }

    private function writeAdminLoginData(): void
    {
        $passwordSalt = $this->shopAdapter->generateUniqueId();

        $password = hash('sha512', $this->adminPassword . $passwordSalt);

        $this->executeSqlQuery("update oxuser set oxusername='{$this->adminUsername}', oxpassword='{$password}', oxpasssalt='{$passwordSalt}' where OXUSERNAME='admin'");
        $this->executeSqlQuery("update oxnewssubscribed set oxemail='{$this->adminUsername}' where OXEMAIL='admin'");
    }
}
