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
 * xarModVars::set($modName, 'databases', serialize($databases));
 * xarModVars::set($modName, 'dbName', 'test');
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
 * $userapi = xarMod::getAPI('library');
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

use Xaraya\Database\ExternalDatabase;
use Connection;
use xarCore;
use xarCoreCache;
use xarDB;
use xarMod;
use xarModVars;
use xarModUserVars;
use xarSession;
use xarUser;
use BadParameterException;
use sys;

sys::import('modules.dynamicdata.class.objects.factory');
sys::import('xaraya.database.external');

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
     * @param mixed $context
     * @return string|null
     */
    public function getCurrentDatabase($context = null);

    /**
     * Summary of setCurrentDatabase
     * @param string $name
     * @param mixed $context
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
 * use sys;
 *
 * sys::import('xaraya.database.databasetrait');
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
    /** @var array<string, mixed> */
    protected static array $_databases = [];
    /** @var array<string, mixed> */
    protected static array $_connections = [];

    /**
     * Summary of getDbModName
     * @return string
     */
    public function getDbModName(): string
    {
        // @todo we rely on the same property as Xaraya\Modules\CoreTrait here (on purpose)
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
            static::$_databases = [];
        }
        $this->moduleName = $modName;
    }

    /**
     * Summary of setModuleName
     * @param string $moduleName
     * @deprecated 2.6.0 use setDbModName() instead
     * @return void
     */
    public function setModuleName($moduleName)
    {
        $this->setDbModName($moduleName);
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
        if (empty(static::$_databases)) {
            $allDatabases = [];
            if (xarCoreCache::isCached('DynamicData', 'Databases')) {
                $allDatabases = xarCoreCache::getCached('DynamicData', 'Databases');
            }
            if (!empty($allDatabases[$this->getDbModName()])) {
                static::$_databases = $allDatabases[$this->getDbModName()];
            } else {
                $databases = unserialize(xarModVars::get($this->getDbModName(), 'databases') ?? '');
                if (empty($databases)) {
                    static::$_databases = [];
                } else {
                    static::$_databases = $databases;
                }
                $allDatabases[$this->getDbModName()] = static::$_databases;
                xarCoreCache::setCached('DynamicData', 'Databases', $allDatabases);
            }
        }
        return static::$_databases;
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
        static::$_databases ??= [];
        if (empty($database)) {
            unset(static::$_databases[$name]);
        } else {
            $database['name'] ??= $name;
            $database['description'] ??= ucwords(str_replace('_', ' ', $name));
            static::$_databases[$name] = $database;
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
        $databases ??= static::$_databases;
        $modName ??= $this->getDbModName();
        xarModVars::set($modName, 'databases', serialize($databases));
        $allDatabases = [];
        if (xarCoreCache::isCached('DynamicData', 'Databases')) {
            $allDatabases = xarCoreCache::getCached('DynamicData', 'Databases');
        }
        $allDatabases[$modName] = $databases;
        xarCoreCache::setCached('DynamicData', 'Databases', $allDatabases);
        // Saved in DD > Utilities > DB Connections = xaradmin/dbconfig.php for all modules - UtilApi::getAllDatabases()
        //xarCoreCache::saveCached('DynamicData', 'Databases');
    }

    /**
     * Summary of connectDatabase
     * @param string $name
     * @return int|string|null
     */
    public function connectDatabase(string $name): int|string|null
    {
        if (!empty(static::$_connections[$name])) {
            return static::$_connections[$name];
        }
        try {
            $args = $this->getDatabaseDSN($name);
        } catch (BadParameterException $e) {
            return null;
        }
        $dbConnIndex = ExternalDatabase::checkDbConnection(null, $args);
        static::$_connections[$name] = $dbConnIndex;
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
     * @param mixed $context
     * @return string|null
     */
    public function getCurrentDatabase($context = null)
    {
        // if we only have one database, return its name
        if (count($this->getDatabases()) === 1) {
            return array_key_first(static::$_databases);
        }
        // we need 'module_itemvars' and/or 'module_vars' tables below
        if (!xarCore::isLoaded(xarCore::SYSTEM_MODULES)) {
            xarMod::loadDbInfo('modules', 'modules');
        }
        if (!empty($context)) {
            $userId = $context->getUserId();
            if (!empty($userId)) {
                // @todo use user context?
                $name = xarModUserVars::get($this->getDbModName(), 'dbName', $userId);
            } else {
                // @todo use session context?
                $name = xarSession::getVar($this->getDbModName() . ':dbName');
            }
        } elseif (xarUser::isLoggedIn()) {
            $name = xarModUserVars::get($this->getDbModName(), 'dbName');
        } else {
            $name = xarSession::getVar($this->getDbModName() . ':dbName');
        }
        if (!isset($name)) {
            $name = xarModVars::get($this->getDbModName(), 'dbName');
        }
        return $name;
    }

    /**
     * Summary of setCurrentDatabase
     * @param string $name
     * @param mixed $context
     * @return void
     */
    public function setCurrentDatabase($name = '', $context = null)
    {
        if (!empty($context)) {
            $userId = $context->getUserId();
            if (!empty($userId)) {
                // @todo use user context?
                xarModUserVars::set($this->getDbModName(), 'dbName', $name, $userId);
            } else {
                // @todo use session context?
                xarSession::setVar($this->getDbModName() . ':dbName', $name);
            }
        } elseif (xarUser::isLoggedIn()) {
            xarModUserVars::set($this->getDbModName(), 'dbName', $name);
        } else {
            xarSession::setVar($this->getDbModName() . ':dbName', $name);
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
        // @todo re-use Database Service to get connection here
        /** @var Connection $conn */
        $conn = xarDB::getConn($dbConnIndex);
        $dbInfo = $conn->getDatabaseInfo();
        $tables = $dbInfo->getTables();
        foreach ($tables as $tblInfo) {
            $result[] = $tblInfo->getName();
        }
        return $result;
    }
}
