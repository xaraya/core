<?php
/**
 * Trait to handle module- or object-specific database connections.
 * See https://github.com/xaraya-modules/library module for an example connecting to sqlite3 databases
 *
 * In modules, you can specify the database(s) by setting module vars:
 * ```
 * $moduleName = 'library';
 * $databases = [
 *     'test' => [
 *         'name' => 'test',
 *         'description' => 'Test Database',
 *         'databaseType' => 'sqlite3',
 *         'databaseName' => 'code/modules/.../xardata/test.db',
 *         // ...other DB params for mysql/mariadb
 *     ],
 * ];
 * xarModVars::set($moduleName, 'databases', serialize($databases));
 * xarModVars::set($moduleName, 'dbName', 'test');
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
 * UserApi::setCurrentDatabase($name)
 * ```
 *
 * @package core\traits
 * @subpackage traits
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\Core\Traits;

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
 */
interface DatabaseInterface
{
    /**
     * Summary of setModuleName
     * @param string $moduleName
     * @return void
     */
    public static function setModuleName($moduleName);

    /**
     * Summary of getDatabases
     * @param ?string $moduleName
     * @return array<string, mixed>
     */
    public static function getDatabases($moduleName = null);

    /**
     * Summary of addDatabase
     * @param string $name
     * @param ?array<mixed> $database db connection args, or null to delete
     * @param bool $save save changes to module vars (default false)
     * @return void
     */
    public static function addDatabase($name, $database, $save = false);

    /**
     * Summary of saveDatabases
     * @param ?array<string, mixed> $databases
     * @param ?string $moduleName
     * @return void
     */
    public static function saveDatabases($databases = null, $moduleName = null);

    /**
     * Summary of connectDatabase
     * @param string $name
     * @return int|null
     */
    public static function connectDatabase($name);

    /**
     * Callable specified in object config to get dbConnArgs for DataObjectMaster
     * Change this if you want to use object-specific database connections
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function getDbConnArgs($object = null);

    /**
     * Summary of getDatabaseDSN
     * @param string $name
     * @throws BadParameterException
     * @return array<string, mixed>
     */
    public static function getDatabaseDSN($name);

    /**
     * Summary of getCurrentDatabase
     * @param mixed $context
     * @return string|null
     */
    public static function getCurrentDatabase($context = null);

    /**
     * Summary of setCurrentDatabase
     * @param string $name
     * @param mixed $context
     * @return void
     */
    public static function setCurrentDatabase($name = '', $context = null);

    /**
     * Summary of getDatabaseTables
     * @param string $name
     * @return array<mixed>
     */
    public static function getDatabaseTables($name);
}

/**
 * Trait to handle module- or object-specific database connections
 *
 * Usage:
 * ```
 * namespace Xaraya\Modules\Library;
 *
 * use Xaraya\Core\Traits\DatabaseInterface;
 * use Xaraya\Core\Traits\DatabaseTrait;
 * use sys;
 *
 * sys::import('xaraya.traits.databasetrait');
 *
 * class UserApi implements DatabaseInterface
 * {
 *     use DatabaseTrait;
 *     protected static string $moduleName = 'library';
 * }
 * ```
 */
trait DatabaseTrait
{
    //protected static string $moduleName = 'OVERRIDE';
    /** @var array<string, mixed> */
    protected static array $_databases = [];
    /** @var array<string, mixed> */
    protected static array $_connections = [];

    /**
     * Summary of setModuleName
     * @param string $moduleName
     * @return void
     */
    public static function setModuleName($moduleName)
    {
        // reset list of databases in DatabaseTrait
        if ($moduleName !== static::$moduleName) {
            static::$_databases = [];
        }
        static::$moduleName = $moduleName;
    }

    /**
     * Summary of getDatabases
     * @param ?string $moduleName
     * @return array<string, mixed>
     */
    public static function getDatabases($moduleName = null)
    {
        if (!empty($moduleName)) {
            static::setModuleName($moduleName);
        }
        if (empty(static::$_databases)) {
            $allDatabases = [];
            if (xarCoreCache::isCached('DynamicData', 'Databases')) {
                $allDatabases = xarCoreCache::getCached('DynamicData', 'Databases');
            }
            if (!empty($allDatabases[static::$moduleName])) {
                static::$_databases = $allDatabases[static::$moduleName];
            } else {
                static::$_databases = unserialize(xarModVars::get(static::$moduleName, 'databases'));
                if (empty(static::$_databases)) {
                    static::$_databases = [];
                }
                $allDatabases[static::$moduleName] = static::$_databases;
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
    public static function addDatabase($name, $database, $save = false)
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
            static::saveDatabases();
        }
    }

    /**
     * Summary of saveDatabases
     * @param ?array<string, mixed> $databases
     * @param ?string $moduleName
     * @return void
     */
    public static function saveDatabases($databases = null, $moduleName = null)
    {
        $databases ??= static::$_databases;
        $moduleName ??= static::$moduleName;
        xarModVars::set($moduleName, 'databases', serialize($databases));
        $allDatabases = [];
        if (xarCoreCache::isCached('DynamicData', 'Databases')) {
            $allDatabases = xarCoreCache::getCached('DynamicData', 'Databases');
        }
        $allDatabases[$moduleName] = $databases;
        xarCoreCache::setCached('DynamicData', 'Databases', $allDatabases);
        // Saved in DD > Utilities > DB Connections = xaradmin/dbconfig.php for all modules - UtilApi::getAllDatabases()
        //xarCoreCache::saveCached('DynamicData', 'Databases');
    }

    /**
     * Summary of connectDatabase
     * @param string $name
     * @return int|string|null
     */
    public static function connectDatabase($name)
    {
        if (!empty(static::$_connections[$name])) {
            return static::$_connections[$name];
        }
        try {
            $args = static::getDatabaseDSN($name);
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
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function getDbConnArgs($object = null)
    {
        $context = null;
        if (is_object($object) && method_exists($object, 'getContext')) {
            $context = $object->getContext();
        }
        $name = static::getCurrentDatabase($context);
        if (!isset($name)) {
            $name = 'memory';
        }
        return static::getDatabaseDSN($name);
    }

    /**
     * Summary of getDatabaseDSN
     * @param string $name
     * @param ?string $moduleName
     * @throws BadParameterException
     * @return array<string, mixed>
     */
    public static function getDatabaseDSN($name, $moduleName = null)
    {
        if ($name == 'memory') {
            return ['databaseType' => 'sqlite3', 'databaseName' => ':memory:'];
        }
        $databases = static::getDatabases($moduleName);
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
    public static function getCurrentDatabase($context = null)
    {
        // if we only have one database, return its name
        if (count(static::getDatabases()) === 1) {
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
                $name = xarModUserVars::get(static::$moduleName, 'dbName', $userId);
            } else {
                // @todo use session context?
                $name = xarSession::getVar(static::$moduleName . ':dbName');
            }
        } elseif (xarUser::isLoggedIn()) {
            $name = xarModUserVars::get(static::$moduleName, 'dbName');
        } else {
            $name = xarSession::getVar(static::$moduleName . ':dbName');
        }
        if (!isset($name)) {
            $name = xarModVars::get(static::$moduleName, 'dbName');
        }
        return $name;
    }

    /**
     * Summary of setCurrentDatabase
     * @param string $name
     * @param mixed $context
     * @return void
     */
    public static function setCurrentDatabase($name = '', $context = null)
    {
        if (!empty($context)) {
            $userId = $context->getUserId();
            if (!empty($userId)) {
                // @todo use user context?
                xarModUserVars::set(static::$moduleName, 'dbName', $name, $userId);
            } else {
                // @todo use session context?
                xarSession::setVar(static::$moduleName . ':dbName', $name);
            }
        } elseif (xarUser::isLoggedIn()) {
            xarModUserVars::set(static::$moduleName, 'dbName', $name);
        } else {
            xarSession::setVar(static::$moduleName . ':dbName', $name);
        }
    }

    /**
     * Summary of getDatabaseTables
     * @param string $name
     * @return array<mixed>
     */
    public static function getDatabaseTables($name)
    {
        $result = [];
        $dbConnIndex = static::connectDatabase($name);
        if (!isset($dbConnIndex)) {
            return $result;
        }
        if (!is_numeric($dbConnIndex)) {
            return ExternalDatabase::listTableNames($dbConnIndex);
        }
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
