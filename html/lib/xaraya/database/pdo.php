<?php
/**
 * PDO wrapper classes
 *
 * The idea here is to mimic all the Xaraya DB calls and their Creole friendly syntax
 * For starters we'll need three classes:
 * - a connection class (extension of PDO)
 * - a statement class (extension of PDOStatement)
 * - a resultset class (we bobble one together)
 *
 * @package core
 * @subpackage database
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Marc Lutolf <marc@luetolf-carroll.com>
 */
sys::import('xaraya.database.interface');
use Xaraya\Database\DatabaseInterface;
use Xaraya\Database\ConnectionInterface;
use Xaraya\Database\StatementInterface;
use Xaraya\Database\ResultSetInterface;


class xarDB_PDO extends xarObject implements DatabaseInterface
{
    /**
     * Map of built-in drivers.
     * Don't think PDO needs this
     */
    private static $driverMap = array();

    // CHECKME: Do we need this?
    public static function configure($dsn, $flags = array(PDO::CASE_LOWER), $prefix = 'xar')
    {
        $persistent = !empty($dsn['persistent']) ? true : false;
        if ($persistent) {
            $flags[] = PDO::ATTR_PERSISTENT;
        }

        self::setFirstDSN($dsn);
        self::setFirstFlags($flags);
        self::setPrefix($prefix);
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
        $flags = array();
        if (isset($args['persistent']) && ! empty($args['persistent'])) {
            $flags[] = PDO::ATTR_PERSISTENT;
        }
        // TODO: add more flags here
		return $flags;
     }

    /**
     * Get the middleware's connection based on dsn and flags
     */

    public static function getConnection(Array $dsn, $flags)
    {
        try {
            $connection = new PDOConnection($dsn, $flags);
        } catch(SQLException $sqle) {
            $sqle->setUserInfo($dsn);
            throw $sqle;
        }
        return $connection;
    }
    
    /**
     * Get the PDO -> ddl type map
     *
     * @return array<int, string>
     */
    public static function getTypeMap()
    {
        return array(
            PDO::PARAM_NULL       => 'null',
            PDO::PARAM_BOOL       => 'boolean',
            PDO::PARAM_STR        => 'text',
            PDO::PARAM_INT        => 'number',
            PDO::PARAM_LOB        => 'binary',
        );
    }
}

//---------------------------------------------------------------------------
/**
 * A class modeling a database connection
 *
 * Creole has this, but PDO doesn't, at least not in a Creole compaible way
 * 
 */

class PDOConnection extends PDO implements ConnectionInterface
{
    private $databaseInfo;

    private $pdo    = null;
    private $dsn    = null;
    private $flags  = null;
    
    public $databaseType  = "PDO";
    public $queryString   = '';
    public $row_count     = 0;
    public $last_id       = null;
    public $dblink        = null;
    public $driverName    = "mysql";

    public function __construct($dsn, $flags = array())
    {
        try {
            $dsnstring = $this->getDSNString($dsn, $flags);
            $this->pdo = parent::__construct($dsnstring, $dsn['username'], $dsn['password'], $flags);
        } catch (PDOException $e) {
            throw $e;
        }
        $this->driverName = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
        // Force PDO to prepare statements
        // CHECKME: setting this to false gives an error with some INSERT statements
        // (missing modules in modules_adminapi_regenerate)
        $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        // Show errors
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $this;
    }

    public function getDSN()
    {
        return $this->dsn;
    }

    public function getFlags()
    {
        return $this->flags;
    }

    // New function defined to get the Mysql version
    public function getResource()
    {
        $mysql_version = $this->query('select version() as server_info')->fetchObject();
        return $mysql_version;
    }

    public function getDatabaseInfo()
    {
        if (null === $this->databaseInfo) {
            $databaseInfo = new PDODatabaseInfo($this);

            // Set up pointer
            $this->databaseInfo = $databaseInfo;
        } else {
            $databaseInfo = $this->databaseInfo;
        }
        return $databaseInfo;
    }

    // Note that commit() and rollback() are the same as in Creole
    public function begin()
    {
        xarLog::message("PDOConnection::begin: starting transaction", xarLog::LEVEL_DEBUG);
        // Only start a transaction of we need to
        if (!PDO::inTransaction()) {
            parent::beginTransaction();
        }
        return true;
    }

    /**
     * @return xarPDOStatement
     */
    public function prepareStatement($string = '')
    {
        $this->queryString = $string;
        $pdostmt = new xarPDOStatement($this);
        return $pdostmt;
    }

    public function qstr($string)
    {
        return "'".str_replace("'", "\\'", $string)."'";
    }

    /**
     * Executes a SQL update and resturns the rows affected
     *
     * @param string $string The query string
     *
     * @return int $affected_rows the rows inserted, changed, dropped
     */
    public function executeUpdate($string = '')
    {
        xarLog::message("PDOConnection::executeUpdate: Executing $string", xarLog::LEVEL_DEBUG);
        try {
            $affected_rows = $this->exec($string);
        } catch (Exception $e) {
            throw $e;
        }
        if (substr(strtoupper($string), 0, 6) == "INSERT") {
            $this->last_id = $this->lastInsertId();
        }
        return $affected_rows;
    }

    /**
     * Executes a SQL query or update and resturn
     *
     * @param string $string the query string
     * @param array<mixed> $bindvars the parameters to be inserted into the query
     * @param int $flag indicates the fetch mode for the results
     *
     * @return object $resultset an object containing the results of the operation
     *
     * Note:
     * - if bindvars are passed we generate a PDO statement and run that
     * - if no bindvars are passed but this is a SELECT, we run PDO's query method and return a PDO statement
     * - Otherwise (no bindvars and not a SELECT, we run PDO's exec method and generate an empty resultset
     */
    public function Execute($string, $bindvars = array(), $flag = 0)
    {
        xarLog::message("PDOConnection::Execute: Executing $string", xarLog::LEVEL_DEBUG);
        try {
            if (empty($flag)) {
                $flag = PDO::FETCH_NUM;
            }

            if (is_array($bindvars) && !empty($bindvars)) {
                // Prepare a SQL statement
                $this->queryString = $string;
                $stmt = new xarPDOStatement($this);
                $result = $stmt->executeQuery($bindvars, $flag);
                return $result;
            } elseif (substr(strtoupper($string), 0, 6) == "SELECT") {
                $stmt = $this->query($string, $flag);
                $this->row_count = $stmt->rowCount();
                $result = new PDOResultSet($stmt, $flag);
                return $result;
            } else {
                $rows_affected = $this->exec($string);
                $this->row_count = $rows_affected;
                if (substr(strtoupper($string), 0, 6) == "INSERT") {
                    $this->last_id = $this->lastInsertId();
                }
                // Create an empty result set
                $result = new PDOResultSet();
                return $result;
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Executes a SQL query
     * Should be a SELECT, but we are supporting updates and inserts, too
     *
     * @param string $string The query string
     * @param int $flag indicates the fetch mode for the results
     *
     * @return object $resultset an object containing the results of the operation
     */
    public function executeQuery($string = '', $flag = 0)
    {
        xarLog::message("PDOConnection::executeQuery: Executing $string", xarLog::LEVEL_DEBUG);
        try {
            if (empty($flag)) {
                $flag = PDO::FETCH_NUM;
            }

            $stmt = $this->query($string);
            if (substr(strtoupper($string), 0, 6) == "INSERT") {
                $this->last_id = $this->lastInsertId();
            }
            $this->row_count = $stmt->rowCount();
            return new PDOResultSet($stmt, $flag);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function SelectLimit($string = '', $limit = 0, $offset = 0, $bindvars = array(), $flag = 0)
    {
        if (empty($flag)) {
            $flag = PDO::FETCH_NUM;
        }
        $limit = empty($limit) ? 1000000 : $limit;

        // TODO: better type testing?
        $limit = $limit < 0 ? -1 : (int)$limit;
        $offset = $offset < 0 ? 0 : (int)$offset;

        // Lets try this the easy way
        // This only works for MySQL !!
        if (substr(strtoupper($string), 0, 6) == "SELECT") {
            // Only dd limit and offset if limit is positive
            if ($limit > 0) {
                $string .= " LIMIT ?";
                $bindvars[] = $limit;
                $string .= " OFFSET ?";
                $bindvars[] = $offset;
            }
        }
        xarLog::message("PDOConnection::SelectLimit: Executing $string", xarLog::LEVEL_DEBUG);
        if (empty($bindvars)) {
            try {
                $stmt = $this->query($string, $flag);
            } catch (Exception $e) {
                throw $e;
            }
            $result = new PDOResultSet($stmt, $flag);
        } else {
            // Prepare a SQL statement
            $this->queryString = $string;
            $stmt = new xarPDOStatement($this);

            // Tell it we alrready added limit and offset
            $stmt->haslimits(true);

            // Execute the SQL statment and create a result set
            try {
                $result = $stmt->executeQuery($bindvars, $flag);
            } catch (Exception $e) {
                throw $e;
            }
        }
        // Save the number of rows
        $this->row_count = $stmt->rowCount();

        return $result;
    }

    public function getUpdateCount()
    {
        return $this->row_count;
    }

    public function PO_Insert_ID($table = null, $field = null)
    {
        return $this->last_id;
    }

    public function getLastId($table = null)
    {
        return $this->last_id;
    }

    public function getNextId($table = null)
    {
        return null;
    }
    public function GenId($table = null)
    {
        return null;
    }
    #[\ReturnTypeWillChange]
    public function commit()
    {
        xarLog::message("PDOConnection::commit: commit transaction", xarLog::LEVEL_DEBUG);
        if (PDO::inTransaction()) {
            parent::commit();
        }
        return true;
    }
    #[\ReturnTypeWillChange]
    public function rollback()
    {
        xarLog::message("PDOConnection::rollback: roll back transaction", xarLog::LEVEL_DEBUG);
        if (PDO::inTransaction()) {
            parent::rollBack();
        }
        return true;
    }

    /**
     * Helper function for assembling a string from the dsn array
     *
     * A string is what PDO needs. Creole works with the dsn array
     */
    private function getDSNString($dsn, $flags)
    {
        switch ($dsn['phptype']) {
        	case 'pdosqlite':
	            $dsnstring  = 'sqlite' . ':' . $dsn['database'];
        	break;
        	case 'pdomysqli':
				$dsnstring  = 'mysql' . ':host=' . $dsn['hostspec'] . ';';
				if (!empty($dsn['port'])) {
					$dsnstring .= 'port=' . $dsn['port'] . ";";
				}
				$dsnstring .= 'dbname=' . $dsn['database'] . ";";
				$dsnstring .= 'charset=' . $dsn['encoding'] . ";";
        	break;
        	case 'pdopgsql':
				$dsnstring  = 'pgsql' . ':host=' . $dsn['hostspec'] . ';';
				if (!empty($dsn['port'])) {
					$dsnstring .= 'port=' . $dsn['port'] . ";";
				}
				$dsnstring .= 'dbname=' . $dsn['database'] . ";";
        	break;
        	default:
			throw new Exception(xarMLS::translate("Unknown database type: '#(1)'", $dsn['phptype']));
        }
		return $dsnstring;
    }
}

//---------------------------------------------------------------------------

class xarPDOStatement extends xarObject implements StatementInterface
{
    private $pdo;
    private $pdostmt;
    private $limit     = 0;
    private $offset    = 0;
    private $haslimits = false;
    private $bindvars;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->prepare($this->pdo->queryString);
    }

    public function haslimits($haslimits)
    {
        $this->haslimits = $haslimits;
        return true;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
        return true;
    }
    public function setOffset($offset)
    {
        $this->offset = $offset;
        return true;
    }

    public function prepare($string)
    {
        $this->pdostmt = $this->pdo->prepare($string);
        return $this->pdostmt;
    }

    public function executeQuery($bindvars = array(), $flag = 0)
    {
        xarLog::message("xarPDOStatement::executeQuery: Preparing " . $this->pdo->queryString, xarLog::LEVEL_DEBUG);
        if (empty($flag)) {
            $flag = PDO::FETCH_NUM;
        }

        // We need to check whether we still have to add limit and offset
        // This only works for MySQL !!
        if (substr(strtoupper($this->pdo->queryString), 0, 6) == "SELECT" && ($this->limit > 0 || $this->offset > 0) && !$this->haslimits) {
            $this->applyLimit($this->pdo->queryString, $this->offset, $this->limit);
        }

        // Add the bindvars to the prepared statement
        $index = 0;
        foreach ($bindvars as $bindvar) {
            $index++;
            if (is_int($bindvar)) {
                $this->pdostmt->bindValue($index, $bindvar, PDO::PARAM_INT);
            } elseif (is_bool($bindvar)) {
                $this->pdostmt->bindValue($index, $bindvar, PDO::PARAM_BOOL);
            } else {
                $this->pdostmt->bindValue($index, $bindvar, PDO::PARAM_STR);
            }
        }

        // Run the query
        xarLog::message("xarPDOStatement::executeQuery: Executing " . $this->pdo->queryString, xarLog::LEVEL_DEBUG);
        try {
            $success = $this->pdostmt->execute();
        } catch (Exception $e) {
            throw $e;
        }

        // If this is a SELECT, create a result set for the results
        if (substr(strtoupper($this->pdo->queryString), 0, 6) == "SELECT") {
            $result = new PDOResultSet($this, $flag);
            // Save the bindvars
            $this->bindvars = $bindvars;
            return $result;
        }

        // If this is an INSERT, get the last inserted ID and return
        if (substr(strtoupper($this->pdo->queryString), 0, 6) == "INSERT") {
            $this->pdo->last_id = $this->pdo->lastInsertId();
            return true;
        }

        // Anything else: just return for now
        return true;
    }

    /**
     * Prepares and executes a SQL update (INSERT, UPDATE, or DELETE) and resturns the rows affected
     *
     * @param array<mixed> $bindvars the parameters to be inserted into the query
     * @param int $flag indicates the fetch mode for the results
     *
     * @return int $affected_rows the rows inserted, changed, dropped
     */
    /* Be insistent and enforce types here */
    public function executeUpdate($bindvars = array(), $flag = 0)
    {
        xarLog::message("xarPDOStatement::executeUpdate: Preparing " . $this->pdo->queryString, xarLog::LEVEL_DEBUG);

        // Add the bindvars to the prepared statement
        $index = 0;
        foreach ($bindvars as $bindvar) {
            $index++;
            if (is_int($bindvar)) {
                $this->pdostmt->bindValue($index, $bindvar, PDO::PARAM_INT);
            } elseif (is_bool($bindvar)) {
                $this->pdostmt->bindValue($index, $bindvar, PDO::PARAM_BOOL);
            } else {
                $this->pdostmt->bindValue($index, $bindvar, PDO::PARAM_STR);
            }
        }

        xarLog::message("xarPDOStatement::executeUpdate: Executing " . $this->pdo->queryString, xarLog::LEVEL_DEBUG);
        try {
            $success = $this->pdostmt->execute();
        } catch (Exception $e) {
            throw $e;
        }

        if (substr(strtoupper($this->pdo->queryString), 0, 6) == "INSERT") {
            $this->pdo->last_id = $this->pdo->lastInsertId();
        }

        // Save the bindvars
        $this->bindvars = $bindvars;

        try {
            $rows_affected = (int) $this->pdostmt->rowCount();
        } catch(PDOException $e) {
            throw new PDOException('Could not get update count ' . $e->getMessage() . $this->pdo->queryString);
        }
        return $rows_affected;
    }

    // Wrappers for the PDOStatement methods
    public function fetchAll($flags)
    {
        if ($this->pdostmt == null) {
            throw new PDOException('No PDOStatement object');
        }
        return $this->pdostmt->fetchAll($flags);
    }
    public function fetch($flags)
    {
        if ($this->pdostmt == null) {
            throw new PDOException('No PDOStatement object');
        }
        return $this->pdostmt->fetch($flags);
    }
    public function rowCount()
    {
        if ($this->pdostmt == null) {
            throw new PDOException('No PDOStatement object');
        }
        return $this->pdostmt->rowCount();
    }
    public function columnCount()
    {
        if ($this->pdostmt == null) {
            throw new PDOException('No PDOStatement object');
        }
        return $this->pdostmt->columnCount();
    }

    private function applyLimit(&$sql, $offset, $limit)
    {
        if ($limit > 0) {
            $sql .= " LIMIT " . ($offset > 0 ? $offset . ", " : "") . $limit;
        } elseif ($offset > 0) {
            $sql .= " LIMIT " . $offset . ", 18446744073709551615";
        }
    }
}

//---------------------------------------------------------------------------
/**
 * DatabaseInfo class: holds the metainformation of the database
 *
 * PDO does not have much metadata, so we have to roll our own here
 *
 */
class PDODatabaseInfo extends xarObject
{
    private $pdo;
    private $tables = [];

    /** have tables been loaded */
    protected $tablesLoaded = false;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getPDO()
    {
        return $this->pdo;
    }

    /**
     * Gets array of TableInfo objects.
     * @return array<mixed>
     */
    public function getTables()
    {
        if (!$this->tablesLoaded) {
            $this->initTables();
        }
        return $this->tables;
    }

    public function getTable($name)
    {
        if (!$this->tablesLoaded) {
            $this->initTables();
        }

        $uppername = strtoupper($name);
        if (!isset($this->tables[$uppername])) {
            return null;
        }
        return $this->tables[$uppername];
    }

    /**
     * @return void
     * @throws PDOException
     */
    private function initTables()
    {
        // get the list of all tables
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) == 'sqlite') {
            $sql = "SELECT name FROM sqlite_master WHERE type='table' UNION ALL SELECT name FROM sqlite_temp_master WHERE type='table' ORDER BY name;";
        } else {
            $sql = "SHOW TABLES";
        }
        try {
            $pdostatement = $this->pdo->query($sql);
        } catch (PDOException $e) {
            throw new PDOException('Could not list tables ' . $e->getMessage() . ': ' . $sql);
        }
        while ($row = $pdostatement->fetch()) {
            $thistable = $this->initTable($row[0]);
            $this->tables[strtoupper($row[0])] = $thistable;
        }
        $this->tablesLoaded = true;
    }

    private function initTable($name)
    {
        $pdotable = new PDOTable($this->pdo);

        // Table name in ghe tables array is upper case by convention
        $uppername = strtoupper($name);

        // If we don't yet have this table's information, then get it
        if (!isset($this->tables[$uppername])) {
            $pdostatement = $this->pdo->query("SELECT * FROM $name LIMIT 0,1");
            $columnarray = array();
            for ($i = 0; $i < $pdostatement->columnCount(); $i++) {
                $column = $pdostatement->getColumnMeta($i);
                $columnarray[$column['name']] = $column;
            }
            $pdotable->setTableName($name);
            $pdotable->setTablecolumns($columnarray);
        }
        return $pdotable;
    }

}

//---------------------------------------------------------------------------
/**
 * PDOTable class: holds the metainformation of a database table
 *
 * PDO does not have much metadata, so we have to roll our own here
 *
 */
class PDOTable extends xarObject
{
    private $pdo;
    private $name;
    private $columns;

    /** have clumns been loaded */
    protected $columnsLoaded = false;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getColumns()
    {
        if (!$this->columnsLoaded) {
            $this->initColumns();
        }
        return $this->columns;
    }

    public function getPrimaryKey()
    {
        // @todo xarPDO middleware only returns primary_key column, not columns for multiple keys
        $columns = $this->getColumns();
        $key_column = '';
        foreach ($columns as $name => $column) {
            // Maybe this is not necessary, but PDO documentation is not very clear
            $flags = $column->getFlags();
            foreach ($flags as $flag) {
                if ($flag == 'primary_key') {
                    $key_column = $column;
                    break;
                }
            }
        }
        if (!empty($key_column)) {
            return $key_column;
        }
        return false;
    }

    public function setTableName($name = '')
    {
        $this->name = $name;
        return true;
    }

    public function setTableColumns($columns = array())
    {
        $this->columns = $columns;
        return true;
    }

    /**
     * @return void
     * @throws PDOException
     */
    protected function initColumns()
    {
        $sql = 'SELECT * FROM ' . $this->getName() . ' LIMIT 0,1';
        try {
            $pdostatement = $this->pdo->query($sql);
        } catch (PDOException $e) {
            throw new PDOException(xarMLS::translate('Could not initialize table columns with: #(1)', $sql));
        }
        $columnarray = array();
        for ($i = 0; $i < $pdostatement->columnCount(); $i++) {
            $columndata = $pdostatement->getColumnMeta($i);
            $column = new PDOColumn($this->pdo);
            $column->setData($columndata);
            $columnarray[$column->getName()] = $column;
        }
        $this->columns = $columnarray;
        $this->columnsLoaded = true;
    }
}

//---------------------------------------------------------------------------
/**
 * PDOTable class: holds the metainformation of a database table
 *
 * PDO does not have much metadata, so we have to roll our own here
 *
 */
class PDOColumn extends xarObject
{
    private $pdo;
    private $columndata = array();
    private $columns = array();

    public $isAutoIncrement;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    public function setData($columndata = array())
    {
        $this->columndata = $columndata;
        return true;
    }
    public function getType()
    {
        return $this->getNativeType();
    }
    public function getNativeType()
    {
        return $this->columndata['native_type'];
    }
    public function getPDOType()
    {
        return $this->columndata['pdo_type'];
    }
    public function getName()
    {
        return $this->columndata['name'];
    }
    public function getFlags()
    {
        return $this->columndata['flags'];
    }
    public function getTable()
    {
        return $this->columndata['table'];
    }
    public function getLength()
    {
        return $this->getSize();
    }
    public function getSize()
    {
        return $this->columndata['len'];
    }
    public function getPrecision()
    {
        return $this->columndata['precision'];
    }
    public function getData()
    {
        return $this->columndata;
    }
    public function isAutoIncrement()
    {
        return $this->isAutoIncrement === true;
    }
    public function getColumns()
    {
        // @todo only used in combination with getPrimaryKey() and never set
        return $this->columns;
    }
    public function getDefaultValue()
    {
        if (!isset($this->columndata['default_value'])) {
            try {
                $sql = "SELECT DEFAULT(" . $this->getName() . ") FROM (SELECT 1) AS dummy LEFT JOIN " . $this->getTable() . " ON True LIMIT 0,1";
                $pdostatement = $this->pdo->query($sql);
            } catch (PDOException $e) {
                // No default value. Return a descriptive string for now
                return 'No value';
                //throw new PDOException(xarMLS::translate('Could not get default value for column #(1) with #(2)', $this->getName(), $sql));
            }
            $value = null;
            while ($row = $pdostatement->fetch()) {
                $value = $row[0];
            }
            $this->columndata['default_value'] = $value;
        }
        return $this->columndata['default_value'];
    }
}

//---------------------------------------------------------------------------
/**
 * ResultSet class: holds the result of a query
 *
 * PDO does not have result sets, so we have to roll our own here
 *
 */
class PDOResultSet extends xarObject implements ResultSetInterface
{
    public const FETCHMODE_ASSOC = PDO::FETCH_ASSOC;
    public const FETCHMODE_NUM   = PDO::FETCH_NUM;
    private $EOF           = 0;

    private $pdostatement;
    private $fetchflag;
    private $valid  = true;
    private $array  = array();

    protected $rtrimString = false;

    public $cursor  = -1;
    public $fields  = array();

    public function __construct($pdostatement = null, $flag = 0)
    {
        // We may not have a PDOSTatment
        if ($pdostatement == null) {
            return;
        }

        // @todo why don't we re-map the flags here, instead of re-defining ResultSet constants for everyone?
        $this->fetchflag = empty($flag) ? self::FETCHMODE_NUM : $flag;
        $this->pdostatement = $pdostatement;
        $this->array = $this->pdostatement->fetchAll($this->fetchflag);
        $this->EOF = count($this->array) === 0;
        // Q: This is an odd Creole legacy. Remove instances of calling $result->fields without next() first
        // A: Actually this dates back from the ADODB time, see https://www.xaraya.hu/rfcs/rfc0035.html#rfc.section.9.3
        if (!empty($this->array)) {
            $this->fields = reset($this->array);
        }
    }

    public function close()
    {
        $this->pdostatement = null;
    }

    public function current()
    {
        return $this->array[$this->cursor];
    }

    public function isAfterLast()
    {
        return ($this->cursor === $this->RecordCount() + 1);
    }

    public function previous()
    {
        // Go back 2 spaces so that we can then advance 1 space.
        $this->cursor = $this->cursor - 2;
        if ($this->cursor > 0) {
            $this->cursor = 0;
            return false;
        }
        return $this->next();
    }

    public function next()
    {
        $this->cursor++;
        $next = $this->getRow();
        $valid = ($next === false) ? false : true;
        if ($this->isAfterLast()) {
            $this->EOF = true;
        }
        return $valid;
    }
    // @todo Remove this in the code
    public function MoveNext()
    {
        return $this->next();
    }

    public function getRow()
    {
        if (empty($this->array[$this->cursor])) {
            return false;
            /**
            $row = $this->pdostatement->fetch($this->fetchflag);
            if (empty ($row)) {
                return false;
            } else {
                $this->array[$this->cursor] = $row;
                $this->fields = $row;
                return $this->fields;
            }
             */
        } else {
            $this->fields = $this->array[$this->cursor];
            return $this->fields;
        }
    }

    public function key()
    {
        return $this->cursor;
    }
    public function valid()
    {
        return $this->valid;
    }
    public function rewind()
    {
        $this->cursor = 0;
    }
    public function first()
    {
        $this->rewind();
        return $this->getRow();
    }
    public function getall()
    {
        return $this->array;
    }

    // Two of these functions is one too many
    public function RecordCount()
    {
        return $this->getRecordCount();
    }
    public function getRecordCount()
    {
        return count($this->array);
    }

    public function setFetchMode($flag)
    {
        // @todo why don't we re-map the flags here, instead of re-defining ResultSet constants for everyone?
        if ($this->fetchflag == $flag) {
            return true;
        }
        $this->fetchflag = $flag;
        $this->pdostatement->closeCursor();
        $this->pdostatement->execute();
        $this->array = $this->pdostatement->fetchAll($this->fetchflag);
        $this->EOF = count($this->array);
        return true;
    }

    public function get($column)
    {
        $col = (is_int($column) ? $column - 1 : $column);
        return $this->array[$this->cursor][$col];
    }
    public function getArray($column)
    {
        $col = (is_int($column) ? $column - 1 : $column);
        if (!array_key_exists($col, $this->fields)) {
            throw new Exception("Invalid resultset column: " . $column);
        }
        if ($this->fields[$col] === null) {
            return null;
        }
        return (array) unserialize($this->fields[$col]);
    }
    public function getBoolean($column)
    {
        $col = (is_int($column) ? $column - 1 : $column);
        if (!array_key_exists($col, $this->fields)) {
            throw new Exception("Invalid resultset column: " . $column);
        }
        if ($this->fields[$col] === null) {
            return null;
        }
        return (bool) $this->fields[$col];
    }
    public function getFloat($column)
    {
        $col = (is_int($column) ? $column - 1 : $column);
        if (!array_key_exists($col, $this->fields)) {
            throw new Exception("Invalid resultset column: " . $column);
        }
        if ($this->fields[$col] === null) {
            return null;
        }
        return (float) $this->fields[$col];
    }
    public function getInt($column)
    {
        $col = (is_int($column) ? $column - 1 : $column);
        if (!array_key_exists($col, $this->fields)) {
            throw new Exception("Invalid resultset column: " . $column);
        }
        if ($this->fields[$col] === null) {
            return null;
        }
        return (int) $this->fields[$col];
    }
    public function getString($column)
    {
        $col = (is_int($column) ? $column - 1 : $column);
        if (!array_key_exists($col, $this->fields)) {
            throw new Exception("Invalid resultset column: " . $column);
        }
        if ($this->fields[$col] === null) {
            return null;
        }
        return ($this->rtrimString ? rtrim($this->fields[$col]) : (string) $this->fields[$col]);
    }

    public function getStatement()
    {
        return $this->pdostatement;
    }
}
