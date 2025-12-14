<?php

/**
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
 * Aligned with xarDB_Creole and xarDB_PDO
 * @todo review xarDB_Interface, as it was common between xarDB and ExternalDatabase,
 * but xarDB extended xarDB_Creole or xarDB_PDO before the DB refactoring by @xaraya
 */
interface xarDB_Interface
{
    /**
     * Summary of getPrefix
     * @return string
     */
    //public static function getPrefix();

    /**
     * Summary of setPrefix
     * @param string $prefix
     * @return void
     */
    //public static function setPrefix($prefix);

    /**
     * Summary of newConn
     * @param ?array<string, mixed> $args
     * @return \Connection|\PDOConnection|object
     */
    //public static function newConn(array $args = null);

    /**
     * Summary of getTables
     * @return array<string, string>
     */
    //public static function &getTables();

    /**
     * Summary of importTables
     * @param array<string, string> $tables
     * @return void
     */
    //public static function importTables(array $tables = array());

    /**
     * Summary of getHost
     * @return string
     */
    //public static function getHost();

    /**
     * Summary of getType
     * @return string
     */
    //public static function getType();

    /**
     * Summary of getName
     * @return string
     */
    //public static function getName();

    //public static function configure($dsn, $flags = -1, $prefix = 'xar');
    //private static function setFirstDSN($dsn = null);
    //private static function setFirstFlags($flags = null);

    /**
     * Summary of getConn
     * @param mixed $index
     * @return \Connection|\PDOConnection|object
     */
    //public static function &getConn($index = 0);

    /**
     * Summary of hasConn
     * @param mixed $index
     * @return bool
     */
    //public static function hasConn($index = 0);

    /**
     * Summary of getConnIndex
     * @return mixed
     */
    //public static function getConnIndex();

    /**
     * Summary of isIndexExternal
     * @param mixed $index
     * @return bool
     */
    public static function isIndexExternal($index = 0);

    /**
     * Summary of getConnection
     * @param array<mixed> $dsn
     * @param mixed $flags
     * @return \Connection|\PDOConnection|object
     */
    public static function getConnection(array $dsn, $flags);

    /**
     * Summary of getTypeMap
     * @return array<mixed>
     */
    public static function getTypeMap();

    /**
     * Summary of getFlags
     * @param mixed $args
     * @return mixed $flags
     */
    // CHECKME: should this be in the interface?
    // public static function getFlags(Array $args=array());
}

// align with Creole Connection - without the Xaraya modifications in ConnectionCommon except Execute()
interface ConnectionInterface
{
    // from Xaraya modifications in ConnectionCommon
    public function log();
    public function setLogger($xarLog);

    /**
     * Summary of Execute
     * @param string $sql
     * @param array<mixed> $bindvars
     * @param ?int $fetchmode
     * @return \ResultSet|\PDOResultSet|object
     */
    public function Execute($sql, $bindvars = [], ?int $fetchmode = null);

    //public function SelectLimit($sql, $limit = 0, $offset = 0, $bindvars = array(), $fetchmode = null);

    //public function connect($dsn, $flags = false);

    /**
     * Summary of getResource
     * @return resource|object
     */
    public function getResource();

    /**
     * Summary of getFlags
     * @return mixed
     */
    public function getFlags();

    /**
     * Summary of getDSN
     * @return mixed
     */
    public function getDSN();

    /**
     * Summary of getDatabaseInfo
     * @return \DatabaseInfo|\PDODatabaseInfo|object
     */
    public function getDatabaseInfo();

    //public function getIdGenerator();

    /**
     * Summary of prepareStatement
     * @param string $sql
     * @return \PreparedStatement|\xarPDOStatement|object
     */
    public function prepareStatement($sql);

    //public function createStatement();

    //public function applyLimit(&$sql, $offset, $limit);

    /**
     * Summary of executeQuery
     * @param string $sql
     * @param ?int $fetchmode
     * @return \ResultSet|\PDOResultSet|object
     */
    public function executeQuery($sql, ?int $fetchmode = null);

    /**
     * Summary of executeUpdate
     * @param string $sql
     * @return mixed
     */
    public function executeUpdate($sql);

    //public function prepareCall($sql);

    //public function close();

    public function isConnected();

    //public function getAutoCommit();

    //public function setAutoCommit($bit);

    /**
     * Summary of begin
     * @return mixed
     */
    public function begin();

    /**
     * Summary of commit
     * @return mixed
     */
    public function commit();

    /**
     * Summary of rollback
     * @return mixed
     */
    public function rollback();

    //public function getUpdateCount();
}

// align with Creole Statement + PreparedStatement - most not used or implemented
interface StatementInterface
{
    /**
     * Summary of setLimit
     * @param mixed $v
     * @return mixed
     */
    public function setLimit($v);

    /**
     * Summary of setOffset
     * @param mixed $v
     * @return mixed
     */
    public function setOffset($v);

    /**
     * Summary of executeQuery
     * @param mixed $p1
     * @param ?int $fetchmode
     * @return mixed
     */
    public function executeQuery($p1 = null, ?int $fetchmode = null);

    /**
     * Summary of executeUpdate
     * @param mixed $params
     * @return mixed
     */
    public function executeUpdate($params = null);
}

// align with Creole ResultSet - without the Xaraya modifications in ResultSetCommon
interface ResultSetInterface
{
    //public function getResource();

    /**
     * Summary of setFetchmode
     * @param int $mode
     * @return mixed
     */
    public function setFetchmode(int $mode);

    //public function getFetchmode();

    //public function isLowerAssocCase();

    /**
     * Summary of next
     * @return mixed
     */
    public function next();

    /**
     * Summary of previous
     * @return mixed
     */
    public function previous();

    //public function relative($offset);

    //public function absolute($pos);

    /**
     * Summary of seek
     * @param mixed $rownum
     * @return mixed
     */
    public function seek($rownum);

    /**
     * Summary of first
     * @return mixed
     */
    public function first();

    //public function last();

    //public function beforeFirst();

    //public function afterLast();

    /**
     * Summary of isAfterLast
     * @return mixed
     */
    public function isAfterLast();

    //public function isBeforeFirst();

    //public function getCursorPos();

    /**
     * Summary of getRow
     * @param ?int $fetchmode
     * @return mixed
     */
    public function getRow(?int $fetchmode = null);

    /**
     * Summary of getRecordCount
     * @return mixed
     */
    public function getRecordCount();

    /**
     * Summary of close
     * @return mixed
     */
    public function close();

    /**
     * Summary of get
     * @param mixed $column
     * @return mixed
     */
    public function get($column = null);

    /**
     * Summary of getArray
     * @param mixed $column
     * @return array<mixed>|null
     */
    public function getArray($column = null);

    /**
     * Summary of getBoolean
     * @param mixed $column
     * @return bool|null
     */
    public function getBoolean($column = null);

    //public function getBlob($column);

    //public function getClob($column);

    //public function getDate($column, $format = '%x');

    /**
     * Summary of getFloat
     * @param mixed $column
     * @return float|null
     */
    public function getFloat($column = null);

    /**
     * Summary of getInt
     * @param mixed $column
     * @return int|null
     */
    public function getInt($column = null);

    /**
     * Summary of getString
     * @param mixed $column
     * @return string|null
     */
    public function getString($column = null);

    //public function getTime($column, $format = '%X');

    //public function getTimestamp($column, $format = 'Y-m-d H:i:s');

    // Extra ResultSetIterator methods are *not* supported in MySQLiResultSet
    //public function rewind();
    //public function valid();
    //public function key();
    //public function current();
    //public function getIterator();
}
