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
 */
interface DatabaseInterface
{
    /**
     * Summary of getPrefix
     * @return string
     */
    public static function getPrefix();
    /**
     * Summary of setPrefix
     * @param string $prefix
     * @return void
     */
    public static function setPrefix($prefix);
    /**
     * Summary of newConn
     * @param ?array<string, mixed> $args
     * @return \Connection|\xarPDO|object
     */
    public static function newConn(array $args = null);
    /**
     * Summary of getTables
     * @return array<string, string>
     */
    public static function &getTables();
    /**
     * Summary of importTables
     * @param array<string, string> $tables
     * @return void
     */
    public static function importTables(array $tables = array());
    /**
     * Summary of getHost
     * @return string
     */
    public static function getHost();
    /**
     * Summary of getType
     * @return string
     */
    public static function getType();
    /**
     * Summary of getName
     * @return string
     */
    public static function getName();
    //public static function configure($dsn, $flags = -1, $prefix = 'xar');
    //private static function setFirstDSN($dsn = null);
    //private static function setFirstFlags($flags = null);
    /**
     * Summary of getConn
     * @param mixed $index
     * @return \Connection|\xarPDO|object
     */
    public static function &getConn($index = 0);
    /**
     * Summary of hasConn
     * @param mixed $index
     * @return bool
     */
    public static function hasConn($index = 0);
    /**
     * Summary of getConnIndex
     * @return mixed
     */
    public static function getConnIndex();
    /**
     * Summary of isIndexExternal
     * @param mixed $index
     * @return bool
     */
    public static function isIndexExternal($index = 0);
    /**
     * Summary of getConnection
     * @param mixed $dsn
     * @param mixed $flags
     * @return \Connection|\xarPDO|object
     */
    public static function getConnection($dsn, $flags = 0);
    /**
     * Summary of getTypeMap
     * @return array<mixed>
     */
    public static function getTypeMap();
}

// align with Creole Connection - without the Xaraya modifications in ConnectionCommon except Execute()
interface ConnectionInterface
{
    // from Xaraya modifications in ConnectionCommon
    /** @return \ResultSet|\PDOResultSet|object */
    public function Execute($sql, $bindvars = array(), $fetchmode = null);
    //public function SelectLimit($sql, $limit = 0, $offset = 0, $bindvars = array(), $fetchmode = null);
    //public function connect($dsn, $flags = false);
    /** @return resource|object */
    public function getResource();
    //public function getFlags();
    //public function getDSN();
    /** @return \DatabaseInfo|\PDODatabaseInfo|object */
    public function getDatabaseInfo();
    //public function getIdGenerator();
    /** @return \PreparedStatement|\xarPDOStatement|object */
    public function prepareStatement($sql);
    //public function createStatement();
    //public function applyLimit(&$sql, $offset, $limit);
    /** @return \ResultSet|\PDOResultSet|object */
    public function executeQuery($sql, $fetchmode = null);
    public function executeUpdate($sql);
    //public function prepareCall($sql);
    //public function close();
    //public function isConnected();
    //public function getAutoCommit();
    //public function setAutoCommit($bit);
    public function begin();
    public function commit();
    public function rollback();
    //public function getUpdateCount();
}

// align with Creole Statement + PreparedStatement - most not used or implemented
interface StatementInterface
{
    public function setLimit($v);
    public function setOffset($v);
    public function executeQuery($p1 = null, $fetchmode = null);
    public function executeUpdate($params = null);
}

// align with Creole ResultSet - without the Xaraya modifications in ResultSetCommon
interface ResultSetInterface
{
    //public function getResource();
    public function setFetchmode($mode);
    //public function getFetchmode();
    //public function isLowerAssocCase();
    public function next();
    public function previous();
    //public function relative($offset);
    //public function absolute($pos);
    //public function seek($rownum);
    public function first();
    //public function last();
    //public function beforeFirst();
    //public function afterLast();
    public function isAfterLast();
    //public function isBeforeFirst();
    //public function getCursorPos();
    public function getRow();
    public function getRecordCount();
    public function close();
    public function get($column);
    public function getArray($column);
    public function getBoolean($column);
    //public function getBlob($column);
    //public function getClob($column);
    //public function getDate($column, $format = '%x');
    public function getFloat($column);
    public function getInt($column);
    public function getString($column);
    //public function getTime($column, $format = '%X');
    //public function getTimestamp($column, $format = 'Y-m-d H:i:s');
    // Extra ResultSetIterator methods are *not* supported in MySQLiResultSet
    //public function rewind();
    //public function valid();
    //public function key();
    //public function current();
    //public function getIterator();
}
