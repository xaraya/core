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
sys::import('creole.Creole');
sys::import('xaraya.database.interface');
use Xaraya\Database\DatabaseInterface;

// @todo why do we extend Creole here again? None of it except a few const and getConnection() are used...
class xarDB_Creole extends Creole implements DatabaseInterface
{
    public static $count = 0;

    // Instead of the globals, we save our db info here.
    private static $firstDSN    = null;
    private static $firstFlags  = null;
    private static $connections = array();
    private static $tables      = array();
    private static $prefix      = '';

    public static function getPrefix() { return self::$prefix;}
    public static function setPrefix($prefix) { self::$prefix =  $prefix; }

/**
 * Initialise a new db connection
 *
 * Create a new connection based on the supplied parameters
 *
 * @return Connection
 */
public static function newConn(array $args = null)
{
    // Minimum for sqlite3 is ['databaseType' => 'sqlite3', 'databaseName' => $filepath] // or ':memory:'
    if ($args['databaseType'] == 'sqlite3') {
        $args['databaseName'] ??= ':memory:';
        $args['databaseHost'] ??= '';
        $args['databasePort'] ??= '';
        $args['userName'] ??= '';
        $args['password'] ??= '';
        $args['databaseCharset'] ??= '';
    }
    // Get database parameters
    $dsn = array('phptype'   => $args['databaseType'],
                 'hostspec'  => $args['databaseHost'],
                 'port'      => $args['databasePort'],
                 'username'  => $args['userName'],
                 'password'  => $args['password'],
                 'database'  => $args['databaseName'],
                 'encoding'  => $args['databaseCharset']);
    // Set flags
    $flags = 0;
    $persistent = !empty($args['persistent']) ? true : false;
    if($persistent) $flags |= Creole::PERSISTENT;
    // if code uses assoc fetching and makes a mess of column names, correct
    // this by forcing returns to be lowercase
    // <mrb> : this is not for nothing a COMPAT flag. the problem still lies
    //         in creating the database schema case sensitive in the first
    //         place. Unfortunately, that is just not portable.
    $flags |= Creole::COMPAT_ASSOC_LOWER;

    try {
        $conn = self::getConnection($dsn,$flags); // cached on dsn hash, so no worries
    } catch (Exception $e) {
        throw $e;
    }
    xarLog::message("New connection created, now serving " . self::$count . " connections", xarLog::LEVEL_NOTICE);
    return $conn;
}
    /**
     * Get an array of database tables
     *
     * @return array<mixed> array of database tables
     * @todo we should figure something out so we dont have to do the getTables stuff, it should be transparent
     */
    public static function &getTables() {  return self::$tables; }

    public static function importTables(Array $tables = array())
    {
        self::$tables = array_merge(self::$tables,$tables);
    }

    public static function getHost() { return self::$firstDSN['hostspec']; }
    public static function getType() { return self::$firstDSN['phptype'];  }
    public static function getName() { return self::$firstDSN['database']; }

    public static function configure($dsn, $flags = Creole::COMPAT_ASSOC_LOWER, $prefix = 'xar')
    {
        $persistent = !empty($dsn['persistent']) ? true : false;
        if ($persistent) $flags |= Creole::PERSISTENT;

        self::setFirstDSN($dsn);
        self::setFirstFlags($flags);
        self::setPrefix($prefix);
    }

    private static function setFirstDSN($dsn = null)
    {
        if(!isset(self::$firstDSN)) {
            if (isset($dsn)) {
                self::$firstDSN = $dsn;
                return;
            }
            $conn = self::$connections[0];
            self::$firstDSN = $conn->getDSN();
        }
    }

    private static function setFirstFlags($flags = null)
    {
        if(!isset(self::$firstFlags)) {
            if (isset($flags)) {
                self::$firstFlags = $flags;
                return;
            }
            $conn = self::$connections[0];
            self::$firstFlags = $conn->getFlags();
        }
    }

    /**
     * Get a database connection
     *
     * @return Connection database connection object
     */
    public static function &getConn($index = 0) 
    { 
      // get connection on demand
      if (count(self::$connections) <= $index && isset(self::$firstDSN) && isset(self::$firstFlags)) {
          self::getConnection(self::$firstDSN, self::$firstFlags);
      }
      // CHECKME:
      // We need to force throwing an exception here
      // Without this the next line halts execution with an error message
      // This happens while installing, before the DB connection has been defined
      if (!isset(self::$connections[$index])) throw new Exception;

      $conn = self::$connections[$index]; 
      return $conn;
    }

    public static function hasConn($index = 0)
    {
        // getConn() above automatically creates another connection to the first DSN on demand
        if (isset(self::$connections[$index])) {
            return true;
        }
        return false;
    }

    // Overridden
    public static function getConnection($dsn, $flags = 0)
    {
        try {
            $conn = Creole::getConnection($dsn, $flags);
        } catch (Exception $e) {
            throw $e;
        }
//        if (!isset($conn)) {
//            return;
//        }
        self::setFirstDSN($conn->getDSN());
        self::setFirstFlags($conn->getFlags());
        self::$connections[] =& $conn;
        self::$count++;
        return $conn;
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
            CreoleTypes::getCreoleCode('VARCHAR')       => 'text',
            CreoleTypes::getCreoleCode('TEXT')          => 'text',
            CreoleTypes::getCreoleCode('CLOB')          => 'text',
            CreoleTypes::getCreoleCode('LONGVARCHAR')   => 'text',
            CreoleTypes::getCreoleCode('INTEGER')       => 'number',
            CreoleTypes::getCreoleCode('TINYINT')       => 'number',
            CreoleTypes::getCreoleCode('BIGINT')        => 'number',
            CreoleTypes::getCreoleCode('SMALLINT')      => 'number',
            CreoleTypes::getCreoleCode('TINYINT')       => 'number',
            CreoleTypes::getCreoleCode('INTEGER')       => 'number',
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
            CreoleTypes::getCreoleCode('VARBINARY')     => 'binary',
            CreoleTypes::getCreoleCode('BLOB')          => 'binary',
            CreoleTypes::getCreoleCode('BINARY')        => 'binary',
            CreoleTypes::getCreoleCode('LONGVARBINARY') => 'binary'
        );
    }
}
