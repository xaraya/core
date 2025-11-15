<?php

/**
 * Trait to handle module- or object-specific database connections.
 * See https://github.com/xaraya-modules/library module for an example connecting to sqlite3 databases
 *
 * In modules, you can specify the database(s) by setting module vars:
 * ```
 * $modName = 'library';
 * $databases = [
 *     'test' => [
 *         'name' => 'test',
 *         'description' => 'Test Database',
 *         'databaseType' => 'sqlite3',
 *         'databaseName' => 'code/modules/.../xardata/test.db',
 *         // ...other DB params for mysql/mariadb
 *     ],
 * ];
 * $this->mod($modName)->setVar('databases', serialize($databases));
 * $this->mod($modName)->setVar('dbName', 'test');
 * ```
 *
 * In objects, you can specify the DB connection args by setting config: (work in progress)
 * ```
 * use Xaraya\Modules\Library\UserApi;
 *
 * $config = ['dbConnIndex' => 1, 'dbConnArgs' => json_encode([UserApi::class, 'getDbConnArgs'])];
 * $descriptor->set('config', serialize($config));
 * ```
 *
 * If you support more than 1 database (besides the Xaraya DB), you can set the current DB for the user with:
 * ```
 * $userapi = xar::mod()->userapi('library');
 * $userapi->setCurrentDatabase($name)
 * ```
 *
 * @package core/database
 * @subpackage database
 * @category Xaraya Web Applications Framework
 * @version 2.5.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\Database;

use Xaraya\Services\WithServicesClass;
use Connection;
use xarCore;
use BadParameterException;

/**
 * For documentation purposes only - available via DatabaseTrait
 *
 * @todo rename to avoid confusion with Database Service? (different namespace)
 */
interface DatabaseInterface
{
    /**
     * Summary of getDbModName
     * @return string
     */
    public function getDbModName(): string;

    /**
     * Summary of setDbModName
     * @param string $modName
     * @return void
     */
    public function setDbModName(string $modName): void;

    /**
     * Summary of getDatabases
     * @param ?string $modName
     * @return array<string, mixed>
     */
    public function getDatabases(?string $modName = null): array;

    /**
     * Summary of addDatabase
     * @param string $name
     * @param ?array<mixed> $database db connection args, or null to delete
     * @param bool $save save changes to module vars (default false)
     * @return void
     */
    public function addDatabase(string $name, ?array $database, bool $save = false): void;

    /**
     * Summary of saveDatabases
     * @param ?array<string, mixed> $databases
     * @param ?string $modName
     * @return void
     */
    public function saveDatabases(?array $databases = null, ?string $modName = null): void;

    /**
     * Summary of connectDatabase
     * @param string $name
     * @return int|string|null
     */
    public function connectDatabase(string $name): int|string|null;

    /**
     * Callable specified in object config to get dbConnArgs for DataObjectMaster
     * Change this if you want to use object-specific database connections
     * @param ?object $object
     * @return array<string, mixed>
     */
    public function getDbConnArgs(?object $object = null): array;

    /**
     * Summary of getDatabaseDSN
     * @param string $name
     * @param ?string $modName
     * @throws BadParameterException
     * @return array<string, mixed>
     */
    public function getDatabaseDSN(string $name, ?string $modName = null): array;

    /**
     * Summary of getCurrentDatabase
     * @param mixed $context for userId
     * @return string|null
     */
    public function getCurrentDatabase($context = null);

    /**
     * Summary of setCurrentDatabase
     * @param string $name
     * @param mixed $context for userId
     * @return void
     */
    public function setCurrentDatabase($name = '', $context = null);

    /**
     * Summary of getDatabaseTables
     * @param string $name
     * @return array<string>
     */
    public function getDatabaseTables(string $name): array;
}

/**
 * Trait to handle module- or object-specific database connections
 *
 * Usage:
 * ```
 * namespace Xaraya\Modules\Library;
 *
 * use Xaraya\Database\DatabaseInterface;
 * use Xaraya\Database\DatabaseTrait;
 *
 * class UserApi implements DatabaseInterface
 * {
 *     use DatabaseTrait;
 * }
 * ```
 *
 * @todo rename to avoid confusion with Database Service? (different namespace)
 */
trait DatabaseTrait
{
    use WithServicesClass;

    /** @var array<string, mixed> */
    protected array $_databases = [];
    /** @var array<string, mixed> */
    protected array $_connections = [];

    /**
     * Summary of getDbModName
     * @return string
     */
    public function getDbModName(): string
    {
        // @todo we rely on the same property as in Xaraya\Modules\*Trait here (on purpose)
        return $this->moduleName;
    }

    /**
     * Summary of setDbModName
     * @param string $modName
     * @return void
     */
    public function setDbModName(string $modName): void
    {
        $this->moduleName ??= $modName;
        // reset list of databases in DatabaseTrait
        if ($modName !== $this->moduleName) {
            $this->_databases = [];
        }
        $this->moduleName = $modName;
    }

    /**
     * Summary of getDatabases
     * @param ?string $modName
     * @return array<string, mixed>
     */
    public function getDatabases(?string $modName = null): array
    {
        if (!empty($modName)) {
            $this->setDbModName($modName);
        }
        $modName = $this->getDbModName();
        if (empty($this->_databases)) {
            $allDatabases = [];
            $xar = $this->getServicesClass();
            if ($xar->mem()->has('DynamicData', 'Databases')) {
                $allDatabases = $xar->mem()->get('DynamicData', 'Databases');
            }
            if (!empty($allDatabases[$modName])) {
                $this->_databases = $allDatabases[$modName];
            } else {
                $databases = unserialize($xar->mod($modName)->getVar('databases') ?? '');
                if (empty($databases)) {
                    $this->_databases = [];
                } else {
                    $this->_databases = $databases;
                }
                $allDatabases[$modName] = $this->_databases;
                $xar->mem()->set('DynamicData', 'Databases', $allDatabases);
            }
        }
        return $this->_databases;
    }

    /**
     * Summary of addDatabase
     * @param string $name
     * @param ?array<mixed> $database db connection args, or null to delete
     * @param bool $save save changes to module vars (default false)
     * @return void
     */
    public function addDatabase(string $name, ?array $database = null, bool $save = false): void
    {
        // allow starting with un-initialized $_databases = before calling getDatabases()
        $this->_databases ??= [];
        if (empty($database)) {
            unset($this->_databases[$name]);
        } else {
            $database['name'] ??= $name;
            $database['description'] ??= ucwords(str_replace('_', ' ', $name));
            $this->_databases[$name] = $database;
        }
        if ($save) {
            $this->saveDatabases();
        }
    }

    /**
     * Summary of saveDatabases
     * @param ?array<string, mixed> $databases
     * @param ?string $modName
     * @return void
     */
    public function saveDatabases(?array $databases = null, ?string $modName = null): void
    {
        $databases ??= $this->_databases;
        $modName ??= $this->getDbModName();
        $xar = $this->getServicesClass();
        $xar->mod($modName)->setVar('databases', serialize($databases));
        $allDatabases = [];
        if ($xar->mem()->has('DynamicData', 'Databases')) {
            $allDatabases = $xar->mem()->get('DynamicData', 'Databases');
        }
        $allDatabases[$modName] = $databases;
        $xar->mem()->set('DynamicData', 'Databases', $allDatabases);
        // Saved in DD > Utilities > DB Connections = modules/dynamicdata/admingui/dbconfig.php
        // for all modules - see UtilApi::getAllDatabases()
        //$xar->mem()->save('DynamicData', 'Databases');
    }

    /**
     * Summary of connectDatabase
     * @param string $name
     * @return int|string|null
     */
    public function connectDatabase(string $name): int|string|null
    {
        if (!empty($this->_connections[$name])) {
            return $this->_connections[$name];
        }
        try {
            $args = $this->getDatabaseDSN($name);
        } catch (BadParameterException $e) {
            return null;
        }
        $xar = $this->getServicesClass();
        $dbConnIndex = $xar->db()->checkDbConnection(null, $args);
        $this->_connections[$name] = $dbConnIndex;
        // return the connection index
        return $dbConnIndex;
    }

    /**
     * Callable specified in object config to get dbConnArgs for DataObjectMaster
     * Change this if you want to use object-specific database connections
     * @param ?object $object
     * @return array<string, mixed>
     */
    public function getDbConnArgs(?object $object = null): array
    {
        $context = null;
        if (is_object($object) && method_exists($object, 'getContext')) {
            $context = $object->getContext();
        }
        $name = $this->getCurrentDatabase($context);
        if (!isset($name)) {
            $name = 'memory';
        }
        return $this->getDatabaseDSN($name);
    }

    /**
     * Summary of getDatabaseDSN
     * @param string $name
     * @param ?string $modName
     * @throws BadParameterException
     * @return array<string, mixed>
     */
    public function getDatabaseDSN(string $name, ?string $modName = null): array
    {
        if ($name == 'memory') {
            return ['databaseType' => 'sqlite3', 'databaseName' => ':memory:'];
        }
        $databases = $this->getDatabases($modName);
        if (!isset($databases[$name])) {
            throw new BadParameterException($name, 'Invalid database name #(1)');
        }
        if (!empty($databases[$name]['disabled'])) {
            throw new BadParameterException($name, 'Disabled database name #(1)');
        }
        return $databases[$name];
    }

    /**
     * Summary of getCurrentDatabase
     * @param mixed $context for userId
     * @return string|null
     */
    public function getCurrentDatabase($context = null)
    {
        // if we only have one database, return its name
        if (count($this->getDatabases()) === 1) {
            return array_key_first($this->_databases);
        }
        $xar = $this->getServicesClass();
        // we need 'module_itemvars' and/or 'module_vars' tables below
        if (!$xar->mod()->isLoaded()) {
            $xar->mod()->loadDbInfo('modules');
        }
        $modName = $this->getDbModName();
        if (!empty($context)) {
            $userId = $context->getUserId();
            if (!empty($userId)) {
                // @todo use user context?
                $name = $xar->mod($modName)->getUserVar('dbName', $userId);
            } else {
                // @todo use session context?
                $name = $xar->session()->getVar($modName . ':dbName');
            }
        } elseif ($xar->user()->isLoggedIn()) {
            $name = $xar->mod($modName)->getUserVar('dbName');
        } else {
            $name = $xar->session()->getVar($modName . ':dbName');
        }
        if (!isset($name)) {
            $name = $xar->mod($modName)->getVar('dbName');
        }
        return $name;
    }

    /**
     * Summary of setCurrentDatabase
     * @param string $name
     * @param mixed $context for userId
     * @return void
     */
    public function setCurrentDatabase($name = '', $context = null)
    {
        $modName = $this->getDbModName();
        $xar = $this->getServicesClass();
        if (!empty($context)) {
            $userId = $context->getUserId();
            if (!empty($userId)) {
                // @todo use user context?
                $xar->mod($modName)->setUserVar('dbName', $name, $userId);
            } else {
                // @todo use session context?
                $xar->session()->setVar($modName . ':dbName', $name);
            }
        } elseif ($xar->user()->isLoggedIn()) {
            $xar->mod($modName)->setUserVar('dbName', $name);
        } else {
            $xar->session()->setVar($modName . ':dbName', $name);
        }
    }

    /**
     * Summary of getDatabaseTables
     * @param string $name
     * @return array<string>
     */
    public function getDatabaseTables(string $name): array
    {
        $result = [];
        $dbConnIndex = $this->connectDatabase($name);
        if (!isset($dbConnIndex)) {
            return $result;
        }
        if (!is_numeric($dbConnIndex)) {
            return ExternalDatabase::listTableNames($dbConnIndex);
        }
        $xar = $this->getServicesClass();
        // @todo re-use Database Service to get connection here
        /** @var Connection $conn */
        $conn = $xar->db()->getConn($dbConnIndex);
        $dbInfo = $conn->getDatabaseInfo();
        $tables = $dbInfo->getTables();
        foreach ($tables as $tblInfo) {
            $result[] = $tblInfo->getName();
        }
        return $result;
    }
}
