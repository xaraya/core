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

use Connection;
use xarDB;
use xarModVars;
use xarModUserVars;
use xarSession;
use xarUser;
use BadParameterException;
use sys;

sys::import('modules.dynamicdata.class.objects.master');

/**
 * For documentation purposes only - available via DatabaseTrait
 */
interface DatabaseInterface
{
    /**
     * Summary of getDatabases
     * @return array<string, mixed>
     */
    public static function getDatabases();

    /**
     * Summary of addDatabase
     * @param string $name
     * @param array<mixed> $database
     * @return void
     */
    public static function addDatabase($name, $database);

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
     * @return string|null
     */
    public static function getCurrentDatabase();

    /**
     * Summary of setCurrentDatabase
     * @param string $name
     * @return void
     */
    public static function setCurrentDatabase($name = '');

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
     * Summary of getDatabases
     * @return array<string, mixed>
     */
    public static function getDatabases()
    {
        if (empty(static::$_databases)) {
            static::$_databases = unserialize(xarModVars::get(static::$moduleName, 'databases'));
            if (empty(static::$_databases)) {
                static::$_databases = [];
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
        static::$_databases ??= [];
        if (empty($database)) {
            unset(static::$_databases[$name]);
        } else {
            $database['name'] ??= $name;
            $database['description'] ??= ucwords(str_replace('_', ' ', $name));
            static::$_databases[$name] = $database;
        }
        if ($save) {
            xarModVars::set(static::$moduleName, 'databases', serialize(static::$_databases));
        }
    }

    /**
     * Summary of connectDatabase
     * @param string $name
     * @return int|null
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
        // open a new database connection
        $conn = xarDB::newConn($args);
        // save the connection index
        $dbConnIndex = xarDB::$count - 1;
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
        $name = static::getCurrentDatabase();
        if (!isset($name)) {
            $name = 'memory';
        }
        return static::getDatabaseDSN($name);
    }

    /**
     * Summary of getDatabaseDSN
     * @param string $name
     * @throws BadParameterException
     * @return array<string, mixed>
     */
    public static function getDatabaseDSN($name)
    {
        if ($name == 'memory') {
            return ['databaseType' => 'sqlite3', 'databaseName' => ':memory:'];
        }
        $databases = static::getDatabases();
        if (!isset($databases[$name])) {
            throw new BadParameterException($name, 'Invalid database name #(1)');
        }
        return $databases[$name];
    }

    /**
     * Summary of getCurrentDatabase
     * @return string|null
     */
    public static function getCurrentDatabase()
    {
        if (xarUser::isLoggedIn()) {
            $name = xarModUserVars::get(static::$moduleName, 'dbName');
        } else {
            $name = xarSession::getVar(static::$moduleName . ':dbName');
        }
        return $name;
    }

    /**
     * Summary of setCurrentDatabase
     * @param string $name
     * @return void
     */
    public static function setCurrentDatabase($name = '')
    {
        if (xarUser::isLoggedIn()) {
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
        /** @var Connection $conn */
        $conn = xarDB::getConn($dbConnIndex);
        $dbInfo = $conn->getDatabaseInfo();
        $tables = $dbInfo->getTables();
        foreach ($tables as $table) {
            $result[] = $table->getName();
        }
        return $result;
    }
}
