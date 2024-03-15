<?php
/**
 * Provide an external database connection to something via PDO/DBAL/... DB driver
 *
 * @package core/database
 * @subpackage database
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Database;

use xarDB;
use sys;

/**
 * Provide an external database connection to something via PDO/DBAL/... DB driver
 *
 * This will return a native PDO/DBAL/... connection of some non-Xaraya type, so use
 * its native methods after that. It's not compliant with ConnectionInterface etc.
 *
 * You can call them directly in your module function or script, or you can specify
 * an external PDO/DBAL/... datastore for DD objects to let it do the work for you.
 *
 * If you do want to return a compatible connection, you'll need to extend the
 * ExternalConnection class and override/implement the abstract methods below...
 */
class ExternalDatabase implements DatabaseInterface
{
    public const INDEX_PREFIX = 'ext_';
    public const ERROR_MSG = 'Not available as static method for ExternalDatabase - use the native methods of the database connection or $datastore->getDatabaseInfo() to get this';
    public static string $latest = '';
    public static string $prefix = "";
    /** @var array<string, \Connection|\PDOConnection|object> */
    public static array $connections = [];
    // if we want to extend this class per DB extension someday + override $connectionClass
    public static string $connectionClass = "ExternalConnection";

    public static function getPrefix()
    {
        // not relevant here?
        return static::$prefix;
    }

    public static function setPrefix($prefix)
    {
        // not relevant here?
        static::$prefix = $prefix;
    }

    public static function newConn(array $args = null)
    {
        $conn = static::getConnection($args);
        return $conn;
    }

    public static function &getTables()
    {
        // this will need to come from the native connection
        throw new \BadMethodCallException(static::ERROR_MSG);
    }

    public static function importTables(array $tables = [])
    {
        // not relevant here
        throw new \BadMethodCallException(static::ERROR_MSG);
    }

    /**
     * Summary of getHost
     * @param mixed $index
     * @throws \BadMethodCallException
     * @return string
     */
    public static function getHost($index = '')
    {
        // this will need to come from the native connection
        if (empty($index)) {
            throw new \BadMethodCallException(static::ERROR_MSG);
        }
        if (is_numeric($index)) {
            return xarDB::getHost();
        }
        // @todo get database host from db connection
        return 'TODO';
    }

    /**
     * Summary of getType
     * @param mixed $index
     * @throws \BadMethodCallException
     * @return string
     */
    public static function getType($index = '')
    {
        // this will need to come from the native connection
        if (empty($index)) {
            throw new \BadMethodCallException(static::ERROR_MSG);
        }
        if (is_numeric($index)) {
            return xarDB::getType();
        }
        // return some information about db driver type from connection
        $conn = static::getConn($index);
        $driverClass = static::getDriverClass($index);
        return $driverClass::getDriverType($conn);
    }

    /**
     * Summary of getName
     * @param mixed $index
     * @throws \BadMethodCallException
     * @return string
     */
    public static function getName($index = '')
    {
        // this will need to come from the native connection
        if (empty($index)) {
            throw new \BadMethodCallException(static::ERROR_MSG);
        }
        if (is_numeric($index)) {
            return xarDB::getName();
        }
        // @todo get database name from db connection
        return 'TODO';
    }

    /**
     * Summary of getDriverName
     * @param mixed $index
     * @throws \BadMethodCallException
     * @return string
     */
    public static function getDriverName($index = '')
    {
        // this will need to come from the native connection
        if (empty($index)) {
            throw new \BadMethodCallException(static::ERROR_MSG);
        }
        if (is_numeric($index)) {
            return 'xaraya';
        }
        $conn = static::getConn($index);
        switch (get_class($conn)) {
            case 'Doctrine\DBAL\Connection':
                return 'dbal';
            case 'MongoDB\Database':
                return 'mongodb';
            case 'PDO':
                return 'pdo';
            default:
                throw new \Exception('Unknown database driver ' . get_class($conn));
        }
    }

    /**
     * Summary of getDriverClass
     * @param mixed $index
     * @throws \BadMethodCallException
     * @return string
     */
    public static function getDriverClass($index = '')
    {
        $driverName = static::getDriverName($index);
        switch ($driverName) {
            case 'dbal':
                // we really need sys::autoload() here
                sys::import('xaraya.database.drivers.dbal');
                return Drivers\DbalDriver::class;
            case 'mongodb':
                // we really need sys::autoload() here
                sys::import('xaraya.database.drivers.mongodb');
                return Drivers\MongoDBDriver::class;
            case 'pdo':
                sys::import('xaraya.database.drivers.pdo');
                return Drivers\PdoDriver::class;
            case 'xaraya':
                // probably not very useful here, but who knows
                return xarDB::class;
            default:
                throw new \Exception('Unknown database driver ' . $driverName);
        }
    }

    //public static function configure($dsn, $flags = -1, $prefix = 'xar');
    //private static function setFirstDSN($dsn = null);
    //private static function setFirstFlags($flags = null);
    public static function &getConn($index = '')
    {
        if (is_numeric($index)) {
            return xarDB::getConn($index);
        }
        if (isset(static::$connections[$index])) {
            return static::$connections[$index];
        }
        throw new \Exception('Invalid db connection index ' . $index);
    }

    public static function hasConn($index = '')
    {
        if (is_numeric($index)) {
            return xarDB::hasConn($index);
        }
        if (isset(static::$connections[$index])) {
            return true;
        }
        return false;
    }

    public static function getConnIndex()
    {
        // index of the latest connection
        return self::$latest;
    }

    public static function isIndexExternal($index = '')
    {
        if (!is_numeric($index) && str_starts_with($index, static::INDEX_PREFIX)) {
            return true;
        }
        return false;
    }

    /**
     * Summary of getConnection
     * @param mixed $dsn
     * @param mixed $flags
     * @return object
     */
    public static function getConnection($dsn, $flags = [])
    {
        // if we want to extend this class per DB extension someday + override $connectionClass
        $dsn['external'] ??= 'default';

        switch ($dsn['external']) {
            case 'pdo':
                sys::import('xaraya.database.drivers.pdo');
                $conn = Drivers\PdoDriver::getConnection($dsn, $flags);
                break;
            case 'dbal':
                // we really need sys::autoload() here
                sys::import('xaraya.database.drivers.dbal');
                $conn = Drivers\DbalDriver::getConnection($dsn, $flags);
                break;
            case 'mongodb':
                // we really need sys::autoload() here
                sys::import('xaraya.database.drivers.mongodb');
                $conn = Drivers\MongoDBDriver::getConnection($dsn, $flags);
                break;
            default:
                // map $dsn and $flags to whatever the connection class expects
                $conn = new static::$connectionClass($dsn, $flags);
                break;
        }
        // avoid false positives when checking is_numeric($dbConnIndex)
        $index = static::INDEX_PREFIX . md5(serialize($dsn));
        static::$connections[$index] = & $conn;
        static::$latest = $index;
        return $conn;
    }

    public static function getTypeMap()
    {
        // this will need to come from the native connection
        throw new \BadMethodCallException(static::ERROR_MSG);
    }

    /**
     * Summary of checkDbConnection
     * @param mixed $dbConnIndex
     * @param array<string, mixed> $dbConnArgs
     * @return mixed
     */
    public static function checkDbConnection($dbConnIndex = 0, $dbConnArgs = [])
    {
        // see if we already have a valid connection (external or not)
        if (!empty($dbConnIndex) && static::hasConn($dbConnIndex)) {
            return $dbConnIndex;
        }
        // we need to make a new connection
        if (!empty($dbConnArgs['external'])) {
            // open a new database connection
            $conn = static::newConn($dbConnArgs);
            // save the connection index
            $dbConnIndex = static::getConnIndex();
        } elseif (!empty($dbConnArgs['databaseType'])) {
            // open a new database connection
            $conn = xarDB::newConn($dbConnArgs);
            // save the connection index
            $dbConnIndex = xarDB::getConnIndex();
        }
        return $dbConnIndex;
    }

    /**
     * Summary of listTableNames
     * @param mixed $index
     * @return array<mixed>
     */
    public static function listTableNames($index)
    {
        $conn = static::getConn($index);
        $driverClass = static::getDriverClass($index);
        return $driverClass::listTableNames($conn);
    }

    /**
     * Summary of listTableColumns
     * @param mixed $index
     * @param string $tablename
     * @return array<string, mixed>
     */
    public static function listTableColumns($index, $tablename)
    {
        $conn = static::getConn($index);
        $driverClass = static::getDriverClass($index);
        return $driverClass::listTableColumns($conn, $tablename);
    }
}

/**
 * Aligned with Creole Connection - without the Xaraya modifications in ConnectionCommon except Execute()
 *
 * If you do want to return a compatible connection, you'll need to extend the
 * ExternalConnection class and override/implement the abstract methods below...
 *
 * @ignore Not meant to be used - for future reference only
 */
abstract class ExternalConnection implements ConnectionInterface
{
    /** @var array<string, mixed> */
    public array $dsn = [];
    public mixed $flags = [];

    /**
     * Summary of __construct
     * @param array<string, mixed> $dsn
     * @param mixed $flags
     */
    public function __construct(array $dsn = null, mixed $flags = [])
    {
        $this->dsn = $dsn;
        $this->flags = $flags;
    }

    // from Xaraya modifications in ConnectionCommon
    /** @return \ResultSet|\PDOResultSet|object */
    abstract public function Execute($sql, $bindvars = [], $fetchmode = null);
    /** @return resource|object */
    abstract public function getResource();
    /** @return \DatabaseInfo|\PDODatabaseInfo|object */
    abstract public function getDatabaseInfo();
    /** @return \PreparedStatement|\xarPDOStatement|object */
    abstract public function prepareStatement($sql);
    /** @return \ResultSet|\PDOResultSet|object */
    abstract public function executeQuery($sql, $fetchmode = null);
    abstract public function executeUpdate($sql);
    abstract public function begin();
    abstract public function commit();
    abstract public function rollback();
}

/**
 * Aligned with Creole Statement + PreparedStatement - most not used or implemented
 *
 * @ignore Not meant to be used - for future reference only
 */
abstract class ExternalStatement implements StatementInterface
{
    abstract public function setLimit($v);
    abstract public function setOffset($v);
    abstract public function executeQuery($p1 = null, $fetchmode = null);
    abstract public function executeUpdate($params = null);
}

/**
 * Aligned with Creole ResultSet - without the Xaraya modifications in ResultSetCommon
 *
 * @ignore Not meant to be used - for future reference only
 */
abstract class ExternalResultSet implements ResultSetInterface
{
    abstract public function setFetchmode($mode);
    abstract public function next();
    abstract public function previous();
    abstract public function first(?int $fetchmode = null);
    abstract public function isAfterLast();
    abstract public function getRow(?int $fetchmode = null);
    abstract public function getRecordCount();
    abstract public function close();
    abstract public function get($column=null);
    abstract public function getArray($column=null);
    abstract public function getBoolean($column=null);
    abstract public function getFloat($column=null);
    abstract public function getInt($column=null);
    abstract public function getString($column=null);
}
