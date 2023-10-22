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
    public const ERROR_MSG = 'Not available as static method for ExternalDatabase - use the native methods of the database connection or $datastore->getDatabaseInfo() to get this';
    public static string $latest = '';
    public static string $prefix = "";
    /** @var array<string, \Connection|\xarPDO|object> */
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

    public static function importTables(array $tables = array())
    {
        // not relevant here
        throw new \BadMethodCallException(static::ERROR_MSG);
    }

    public static function getHost()
    {
        // this will need to come from the native connection
        throw new \BadMethodCallException(static::ERROR_MSG);
    }

    public static function getType()
    {
        // this will need to come from the native connection
        throw new \BadMethodCallException(static::ERROR_MSG);
    }

    public static function getName()
    {
        // this will need to come from the native connection
        throw new \BadMethodCallException(static::ERROR_MSG);
    }

    //public static function configure($dsn, $flags = -1, $prefix = 'xar');
    //private static function setFirstDSN($dsn = null);
    //private static function setFirstFlags($flags = null);
    public static function &getConn($index = 0)
    {
        if (isset(static::$connections[$index])) {
            return static::$connections[$index];
        }
        throw new \Exception("Invalid index $index");
    }

    public static function hasConn($index = 0)
    {
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
                $conn = static::getPdoConnection($dsn, $flags);
                break;
            case 'dbal':
                $conn = static::getDbalConnection($dsn, $flags);
                break;
            default:
                // map $dsn and $flags to whatever the connection class expects
                $conn = new static::$connectionClass($dsn, $flags);
                break;
        }
        // avoid false positives when checking is_numeric($dbConnIndex)
        $index = 'ext_' . md5(serialize($dsn));
        static::$connections[$index] = & $conn;
        static::$latest = $index;
        return $conn;
    }

    public static function getPdoConnection($dsn, $flags = [])
    {
        [$dsn, $username, $password, $options] = static::mapPdoDSN($dsn, $flags);
        return new \PDO($dsn, $username, $password, $options);
    }

    public static function mapPdoDSN($dsn, $flags = [])
    {
        $username = $dsn['userName'] ?? null;
        $password = $dsn['password'] ?? null;
        $options = $flags;
        // check if $dsn already contain PDO-compatible parameters
        if (!empty($dsn['dsnstring'])) {
            // @todo check username for other variants if needed
            $dsnstring = $dsn['dsnstring'];
            return [$dsnstring, $username, $password, $options];
        }
        // map Xaraya connection arguments to PDO-compatible parameters
        $parts = [];
        if (!empty($dsn['databaseHost'])) {
            if (str_starts_with($dsn['databaseHost'], '/') && str_contains($dsn['databaseType'], 'mysql')) {
                $parts[] = 'unix_socket=' . $dsn['databaseHost'];
            } else {
                $parts[] = 'host=' . $dsn['databaseHost'];
            }
        }
        if (!empty($dsn['databasePort'])) {
            $parts[] = 'port=' . $dsn['databasePort'];
        }
        if (!empty($dsn['databaseName'])) {
            if (empty($dsn['databaseHost']) && str_contains($dsn['databaseType'], 'sqlite')) {
                // DSN string is sqlite:/home/xaraya-core/html/var/sqlite/xaraya.db
                $parts[] = $dsn['databaseName'];
            } else {
                $parts[] = 'dbname=' . $dsn['databaseName'];
            }
        }
        //if (!empty($dsn['userName'])) {
        //    $parts[] = 'user=' . $dsn['userName'];
        //}
        //if (!empty($dsn['password'])) {
        //    $parts[] = 'password=' . $dsn['password'];
        //}
        if (!empty($dsn['databaseCharset'])) {
            $parts[] = 'charset=' . $dsn['databaseCharset'];
        }
        // we want to get an exception if databaseType is not defined
        $dsnstring = $dsn['databaseType'] . ':' . implode(';', $parts);
        return [$dsnstring, $username, $password, $options];
    }

    public static function getDbalConnection($dsn, $flags)
    {
        $params = static::mapDbalDSN($dsn, $flags);
        return \Doctrine\DBAL\DriverManager::getConnection($params);
    }

    public static function mapDbalDSN($dsn, $flags = [])
    {
        // check if $dsn already contain DBAL-compatible parameters
        if (!empty($dsn['driver']) && (!empty($dsn['dbname']) || !empty($dsn['path']))) {
            return $dsn;
        }
        // map Xaraya connection arguments to DBAL-compatible parameters
        $params = [];
        // we want to get an exception if databaseType is not defined
        $params['driver'] = $dsn['databaseType'];
        if (empty($dsn['databaseHost']) && str_contains($params['driver'], 'sqlite')) {
            $params['path'] = $dsn['databaseName'];
        } else {
            $params['dbname'] = $dsn['databaseName'];
        }
        if (!empty($dsn['databaseHost'])) {
            if (str_starts_with($dsn['databaseHost'], '/') && str_contains($params['driver'], 'mysql')) {
                $params['unix_socket'] = $dsn['databaseHost'];
            } else {
                $params['host'] = $dsn['databaseHost'];
            }
        }
        if (!empty($dsn['databasePort'])) {
            $params['port'] = $dsn['databasePort'];
        }
        if (!empty($dsn['userName'])) {
            $params['user'] = $dsn['userName'];
        }
        if (!empty($dsn['password'])) {
            $params['password'] = $dsn['password'];
        }
        if (!empty($dsn['databaseCharset'])) {
            $params['charset'] = $dsn['databaseCharset'];
        }
        return $params;
    }

    public static function getTypeMap()
    {
        // this will need to come from the native connection
        throw new \BadMethodCallException(static::ERROR_MSG);
    }
}

/**
 * Aligned with Creole Connection - without the Xaraya modifications in ConnectionCommon except Execute()
 *
 * If you do want to return a compatible connection, you'll need to extend the
 * ExternalConnection class and override/implement the abstract methods below...
 */
abstract class ExternalConnection implements ConnectionInterface
{
    public array $dsn = [];
    public mixed $flags = [];

    public function __construct(array $dsn = null, mixed $flags = [])
    {
        $this->dsn = $dsn;
        $this->flags = $flags;
    }

    // from Xaraya modifications in ConnectionCommon
    /** @return \ResultSet|\PDOResultSet|object */
    abstract public function Execute($sql, $bindvars = array(), $fetchmode = null);
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
 */
abstract class ExternalResultSet implements ResultSetInterface
{
    abstract public function setFetchmode($mode);
    abstract public function next();
    abstract public function previous();
    abstract public function first();
    abstract public function isAfterLast();
    abstract public function getRow();
    abstract public function getRecordCount();
    abstract public function close();
    abstract public function get($column);
    abstract public function getArray($column);
    abstract public function getBoolean($column);
    abstract public function getFloat($column);
    abstract public function getInt($column);
    abstract public function getString($column);
}
