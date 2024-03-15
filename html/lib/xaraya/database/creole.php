<?php
/**
 * Creole wrapper class
 * @todo stop extending Creole for xarDB_Creole class
 *
 * The idea here is to put all deviations/additions/correction from creole
 * into this class. All generic improvement should be  pushed upstream obviously
 *
 * @package core
 * @subpackage database
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marcel van der Boom <marcel@hsdev.com>
 */
sys::import('xaraya.database.interface');
sys::import('creole.Creole');
use Xaraya\Database\DatabaseInterface;

class xarDB_Creole extends xarObject implements DatabaseInterface
{
    /**
     * Map of built-in drivers.
     * @var array Hash mapping phptype => driver class (in dot-path notation, e.g. 'mysql' => 'creole.drivers.mysql.MySQLConnection').
     */
    public static $driverMap = array(   'mysql'      => 'creole.drivers.mysql.MySQLConnection',
                                        'mysqli'     => 'creole.drivers.mysqli.MySQLiConnection',
                                        'pgsql'      => 'creole.drivers.pgsql.PgSQLConnection',
                                        'sqlite'     => 'creole.drivers.sqlite.SQLiteConnection',
                                        'oracle'     => 'creole.drivers.oracle.OCI8Connection',
                                        'mssql'      => 'creole.drivers.mssql.MSSQLConnection',
                                        'odbc'       => 'creole.drivers.odbc.ODBCConnection',
                                        'pdosqlite'  => 'creole.drivers.pdosqlite.PdoSQLiteConnection',
                                        'pdosqlite2' => 'creole.drivers.pdosqlite.PdoSQLiteConnection',
                                        'sqlite3'    => 'creole.drivers.sqlite.SQLiteConnection',
                                       );

    public static function getDrivers()
    {
    	return self::$driverMap;
    }

    // CHECKME: Do we need this? I don't think so...
    public static function configure($dsn, $flags = Creole::COMPAT_ASSOC_LOWER, $prefix = 'xar')
    {
        $persistent = !empty($dsn['persistent']) ? true : false;
        if ($persistent) {
            $flags |= Creole::PERSISTENT;
        }

        //self::setFirstDSN($dsn);
        //self::setFirstFlags($flags);
        //self::setPrefix($prefix);
    }

    public static function isIndexExternal($index = 0)
    {
        return false;
    }

    /**
     * Get the flags in a proper form for this middleware
     */
    public static function getFlags(Array $args=array())
     {
        $flags = 0;
        if (isset($args['persistent']) && ! empty($args['persistent'])) {
            $flags |= Creole::PERSISTENT;
        }
/*
        if (isset($args['compat_assoc_lower']) && ! empty($args['compat_assoc_lower'])) {
            $flags |= Creole::COMPAT_ASSOC_LOWER;
        }
*/
        if (isset($args['compat_rtrim_string']) && ! empty($args['compat_rtrim_string'])) {
            $flags |= Creole::COMPAT_RTRIM_STRING;
        }
        if (isset($args['compat_all']) && ! empty($args['compat_all'])) {
            $flags |= Creole::COMPAT_ALL;
        }
        // if code uses assoc fetching and makes a mess of column names, correct
        // this by forcing returns to be lowercase
        // <mrb> : this is not for nothing a COMPAT flag. the problem still lies
        //         in creating the database schema case sensitive in the first
        //         place. Unfortunately, that is just not portable.
        $flags |= Creole::COMPAT_ASSOC_LOWER;
        
        return $flags;
     }
     
    /**
     * Get the middleware's connection based on dsn and flags
     */

    public static function getConnection(Array $dsn, $flags = 0)
    {
        // support "catchall" drivers which will themselves handle the details of connecting
        // using the proper RDBMS driver.
        $drivers = self::getDrivers();
        if (isset($drivers['*'])) {
            $type = '*';
        } else {
            $type = $dsn['phptype'];
            if (!isset($drivers[$type])) {
                throw new SQLException("No driver has been registered to handle connection type: $type");
            }
        }

        // may need to make this more complex if we add support
        // for 'dbsyntax'
        $clazz = self::import($drivers[$type]);
        $connection = new $clazz();

        if (!($connection instanceof Connection)) {
            throw new SQLException("Class does not implement creole.Connection interface: $clazz");
        }

        try {
            $connection->connect($dsn, $flags);
        } catch(SQLException $sqle) {
            $sqle->setUserInfo($dsn);
            throw $sqle;
        }
        return $connection;
    }
    
    /**
     * Include once a file specified in DOT notation.
     * Package notation is expected to be relative to a location
     * on the PHP include_path.
     * @param string $class
     * @return string unqualified classname
     * @throws SQLException - if class does not exist and cannot load file
     *                      - if after loading file class still does not exist
     */
    public static function import($class)
    {
        $pos = strrpos($class, '.');
        // get just classname ('path.to.ClassName' -> 'ClassName')
        if ($pos !== false) {
            $classname = substr($class, $pos + 1);
        } else {
            $classname = $class;
        }

        if (!class_exists($classname, false)) {
            $path = strtr($class, '.', DIRECTORY_SEPARATOR) . '.php';
            $ret = @include_once($path);
            if ($ret === false) {
                throw new SQLException("Unable to load driver class: " . $class);
            }
            if (!class_exists($classname)) {
                throw new SQLException("Unable to find loaded class: $classname (Hint: make sure classname matches filename)");
            }
        }
        return $classname;
    }

    /**
     * Get the creole -> ddl type map
     *
     * @return array<mixed>
     */
    public static function getTypeMap()
    {
        sys::import('creole.CreoleTypes');
        return array(
            CreoleTypes::getCreoleCode('BOOLEAN')       => 'boolean',
            CreoleTypes::getCreoleCode('VARCHAR')       => 'text',
            CreoleTypes::getCreoleCode('LONGVARCHAR')   => 'text',
            CreoleTypes::getCreoleCode('CHAR')          => 'text',
            CreoleTypes::getCreoleCode('TEXT')          => 'text',
            CreoleTypes::getCreoleCode('CLOB')          => 'text',
            CreoleTypes::getCreoleCode('INTEGER')       => 'number',
            CreoleTypes::getCreoleCode('TINYINT')       => 'number',
            CreoleTypes::getCreoleCode('BIGINT')        => 'number',
            CreoleTypes::getCreoleCode('SMALLINT')      => 'number',
            CreoleTypes::getCreoleCode('FLOAT')         => 'number',
            CreoleTypes::getCreoleCode('NUMERIC')       => 'number',
            CreoleTypes::getCreoleCode('DECIMAL')       => 'number',
            CreoleTypes::getCreoleCode('YEAR')          => 'number',
            CreoleTypes::getCreoleCode('REAL')          => 'number',
            CreoleTypes::getCreoleCode('DOUBLE')        => 'number',
            CreoleTypes::getCreoleCode('DATE')          => 'time',
            CreoleTypes::getCreoleCode('TIME')          => 'time',
            CreoleTypes::getCreoleCode('TIMESTAMP')     => 'time',
            CreoleTypes::getCreoleCode('VARBINARY')     => 'binary',
            CreoleTypes::getCreoleCode('BLOB')          => 'binary',
            CreoleTypes::getCreoleCode('BINARY')        => 'binary',
            CreoleTypes::getCreoleCode('LONGVARBINARY') => 'binary'
        );
    }
}
