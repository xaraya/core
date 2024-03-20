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
    public static $driverMap = array();

    // CHECKME: Do we need this? I don't think so...
    public static function configure($dsn, $flags = array(PDO::CASE_LOWER), $prefix = 'xar')
    {
        $persistent = !empty($dsn['persistent']) ? true : false;
        if ($persistent) {
            $flags[] = PDO::ATTR_PERSISTENT;
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
 */

class PDOConnection extends PDO implements ConnectionInterface
{
    private $databaseInfo;

    private $dsn    = null;
    private $flags  = null;
    
    public $databaseType  = "PDO";
    public $queryString   = '';
    public $row_count     = 0;
    public $last_id       = null;
    public $driverName    = "mysql";

    public function __construct($dsn, $flags = array())
    {
        try {
            $dsnstring = $this->getDSNString($dsn, $flags);
            parent::__construct($dsnstring, $dsn['username'], $dsn['password'], $flags);
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
            $this->last_id = $this->pdo->lastInsertId();
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
    public function Execute($string, $bindvars = array(), ?int $fetchmode = null)
    {
        xarLog::message("PDOConnection::Execute: Executing $string", xarLog::LEVEL_DEBUG);
        try {
        	$fetchmode = $fetchmode ?? PDO::FETCH_NUM;

            if (is_array($bindvars) && !empty($bindvars)) {
                // Prepare a SQL statement
                $this->queryString = $string;
                $stmt = new xarPDOStatement($this);
                $result = $stmt->executeQuery($bindvars, $fetchmode);
                return $result;
            } elseif (substr(strtoupper($string), 0, 6) == "SELECT") {
                $stmt = $this->query($string, $fetchmode);
                $this->row_count = $stmt->rowCount();
                $result = new PDOResultSet($stmt, $fetchmode);
                return $result;
            } else {
                $rows_affected = $this->exec($string);
                $this->row_count = $rows_affected;
                if (substr(strtoupper($string), 0, 6) == "INSERT") {
                    $this->last_id = $this->pdo->lastInsertId();
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
     * @param int $fetchmode indicates the fetch mode for the results
     *
     * @return object $resultset an object containing the results of the operation
     */
    public function executeQuery($string = '', ?int $fetchmode = null)
    {
        xarLog::message("PDOConnection::executeQuery: Executing $string", xarLog::LEVEL_DEBUG);
        try {
            $fetchmode = $fetchmode ?? PDO::FETCH_NUM;

            $stmt = $this->query($string);
            if (substr(strtoupper($string), 0, 6) == "INSERT") {
                $this->last_id = $this->pdo->lastInsertId();
            }
            $this->row_count = $stmt->rowCount();
            return new PDOResultSet($stmt, $fetchmode);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function SelectLimit($string = '', $limit = 0, $offset = 0, $bindvars = array(), ?int $fetchmode = null)
    {
        $fetchmode = $fetchmode ?? PDO::FETCH_NUM;
        
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
                $stmt = $this->query($string, $fetchmode);
            } catch (Exception $e) {
                throw $e;
            }
            $result = new PDOResultSet($stmt, $fetchmode);
        } else {
            // Prepare a SQL statement
            $this->queryString = $string;
            $stmt = new xarPDOStatement($this);

            // Tell it we alrready added limit and offset
            $stmt->haslimits(true);

            // Execute the SQL statment and create a result set
            try {
                $result = $stmt->executeQuery($bindvars, $fetchmode);
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
    private $fetchmode = PDO::FETCH_NUM;	// The default for getting database rows for all middlewares

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

    public function executeQuery($bindvars = array(), ?int $fetchmode = null)
    {
        xarLog::message("xarPDOStatement::executeQuery: Preparing " . $this->pdo->queryString, xarLog::LEVEL_DEBUG);

		$fetchmode = $fetchmode ?? $this->fetchmode;

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

		$success = $this->pdostmt->execute();
		if (!$success) {
			throw new SQLException("PDO: SELECT query " . $this->pdo->queryString . " failed to execute");
		}

        switch (substr(strtoupper($this->pdo->queryString), 0, 6) == "SELECT") {
        	case 'SELECT':
				// If this is a SELECT, create a result set for the results
				$result = new PDOResultSet($this, $fetchmode);
				// Save the bindvars
				$this->bindvars = $bindvars;
			break;
			case 'INSERT':
        		// If this is an INSERT, get the last inserted ID and return
	            $this->last_id = $this->pdo->lastInsertId();
	            $result = true;
			break;
			default:
		        // Anything else: just return for now
	            $result = true;
        }
        return $result;
    }

    /**
     * Prepares and executes a SQL update (INSERT, UPDATE, or DELETE) and resturns the rows affected
     *
     * @param array<mixed> $bindvars the parameters to be inserted into the query
     *
     * @return int $affected_rows the rows inserted, changed, dropped
     */
    /* Be insistent and enforce types here */
    public function executeUpdate($bindvars = array())
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

		$success = $this->pdostmt->execute();
		if (!$success) {
			throw new SQLException("PDO: UPDATE query " . $this->pdo->queryString . " failed to execute");
		}

        if (substr(strtoupper($this->pdo->queryString), 0, 6) == "INSERT") {
            $this->last_id = $this->pdo->lastInsertId();
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
    public function fetchAll(?int $fetchmode = null)
    {
        if (null === $this->pdostmt) {
            throw new PDOException('No PDOStatement object');
        }
        if (null ===  $fetchmode) $fetchmode = $this->fetchmode;
        return $this->pdostmt->fetchAll($fetchmode);
    }
    public function fetch(?int $fetchmode = null)
    {
        if (null === $this->pdostmt) {
            throw new PDOException('No PDOStatement object');
        }
        $fetchmode = $fetchmode ?? $this->fetchmode;

		 $result = $this->pdostmt->fetch($fetchmode);
		return $result;
    }
    public function rowCount()
    {
        if (null === $this->pdostmt) {
            throw new PDOException('No PDOStatement object');
        }
        return $this->pdostmt->rowCount();
    }
    public function columnCount()
    {
        if (null === $this->pdostmt) {
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
 * PDO does not have result sets, so we have to roll our own here.
 * Ideally this would be done in PHP's PDOStatement class, which has an iterator.
 * Some of the methods don't seem to work however and the documentation is nil.
 * So for now we create our own array of the resultset data and work with that.
 *
 */
class PDOResultSet extends xarObject implements ResultSetInterface
{
    private $pdostatement;
    private $valid  = true;
    private $array  = array();				// Holds an array of the resultset's data
    private $cursor  = 0;					// A pointer for our current position in the resultset array. 
    private $fetchmode = PDO::FETCH_NUM;	// The default for getting database rows for all middlewares

    protected $rtrimString = false;

    public $fields = array();				// Holds an array of the resultset's fields (column names)
    public $EOF = true;						// A flag we need to get rid off, but alas

    public function __construct($pdostatement = null, ?int $fetchmode = null)
    {
        // We may not have a PDOStatement
        if (null === $pdostatement) {
            return;
        }

        $this->pdostatement = $pdostatement;
        if (null != $fetchmode) {
			$this->fetchmode = $fetchmode;
        }
        
        // We need an associative array here so that we can support changing $fetchmode downstream.
        // This is done in the refreshkeys method.
        // This choice has downstream consequences, since there are occasionally multi-table queries in the codebase that 
        // give wrong results when forced to associative fetchmode, i.e. some fields from different tables have the same name. 
        // The problem can be resolved by adding aliases to said queries.
        // Queries using the $query abstraction don't have this issue.
        $this->array = $this->pdostatement->fetchAll(PDO::FETCH_ASSOC);

        // Put the first row into the fields array and set the cursor to zero
        if (!empty($this->array)) {
            $this->cursor = 0;
			$this->fields = reset($this->array);
        }
    }

    public function getFetchMode()
    {
        return $this->fetchmode;
    }

    public function setFetchMode($fetchmode)
    {
        $this->fetchmode = $fetchmode;
		$this->refresh_keys(0, $fetchmode);
        return true;
    }

//---------------------------------------------------------------------------
/**
 * Movement methods
 * These methods move the cursor and return true/false
 * These methods take their fields values for refreshing fetchmode from the results array
 */

    public function first()
    {
        if($this->cursor !== 0) {
            $this->seek(0);
        }
		$this->refresh_keys(0, $this->fetchmode);
		return !empty($this->fields);
    }
    
    public function current()
    {
		$fetchmode = $fetchmode ?? $this->fetchmode;

        $this->refresh_keys(0, $this->fetchmode);
		return !empty($this->fields);
    }

    public function last()
    {
        if($this->cursor !==  ($last = $this->getRecordCount() - 1)) {
            $this->seek($last);
        }
        $this->refresh_keys(0, $this->fetchmode);
		return !empty($this->fields);
    }

    /**
     * @param int $rownum
     */
    public function seek($rownum = 0)
    {
        if (!$this->inBounds()) {
            return false;
        }
        $this->cursor = $rownum;
		$this->refresh_keys(1, $this->fetchmode);
        return true;
    }
    
    public function previous()
    {
        if (!$this->inBounds()) {
            return false;
        }

		// Adjust the field keys to the fetchmode
		$this->refresh_keys(1, $this->fetchmode);
        // Advance the cursor
        $this->cursor--;
        return true;
    }

    public function next()
    {
        if (!$this->inBounds()) {
        	$this->EOF = true;
            return false;
        }

		// Adjust the field keys to the fetchmode
		$this->refresh_keys(1, $this->fetchmode);
        // Advance the cursor
        $this->cursor++;
        return true;
    }
    // @todo Remove this in the code
    public function MoveNext()
    {
        return $this->next();
    }
    
    public function rewind()
    {
        $this->seek(0);
//        $this->refresh_keys(1, $this->fetchmode);
        return true;
    }

//---------------------------------------------------------------------------
/**
 * Retrieval methods
 * These methods return rows 
 * These methods take their fields values for refreshing fetchmode from the fields array
 */
    public function getRow(?int $fetchmode=null)
    {
		$fetchmode = $fetchmode ?? $this->fetchmode;

		$this->refresh_keys(0, $fetchmode);
		return $this->fields;
    }
    
    // TODO: remove this from the code
    public function fetchRow(?int $fetchmode=null)
    {
        return $this->getRow($fetchmode);
    }

    public function getall(?int $fetchmode=null)
    {
		$fetchmode = $fetchmode ?? $this->fetchmode;
		
		// By definition $this->array is associative, so if we have FETCH_NUM
		// we need to remove the associative keys
		if ($fetchmode == PDO::FETCH_NUM) {
			$results_array = array();
			foreach ($this->array as $values) {
				$results_array[] = array_values($values);
			}
		} else {
			return $this->array;
		}
        return $results_array;
    }

//---------------------------------------------------------------------------
    public function close()
    {
        $this->pdostatement = null;
    }

    public function inBounds(?int $rownum=null)
    {
        $rownum = $rownum ?? $this->cursor;

        // We need a valid key value and a non empty results array 
        $bounds = array_key_exists($rownum, $this->array) && ($this->getRecordCount() !== 0);
        return $bounds;
    }

    public function isBeforeFirst()
    {
        $outofbounds = ($this->cursor === -1) || ($this->getRecordCount() === 0);
        return $outofbounds;
    }

    public function isAfterLast()
    {
        $outofbounds = ($this->cursor === $this->getRecordCount() + 1) || ($this->getRecordCount() === 0);
        return $outofbounds;
    }

    public function key()
    {
        return $this->cursor;
    }
    public function valid()
    {
        return $this->valid;
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

    public function getStatement()
    {
        return $this->pdostatement;
    }

//---------------------------------------------------------------------------
/**
 * Column retrieval methods
 * These methods return raw and type cast column values
 * The column numbers here begin with 1, not 0!!
 */
    public function get($column=null)
    {
        $col = (is_int($column) ? $column - 1 : $column);
        if ((null === $col) || !isset($this->fields[$col])) {
        	return false;
        }
        return $this->fields[$col];
    }
    
    public function getArray($column=null)
    {
        if (null === $col = $this->checkColGet($column)) {
        	return null;
        }
        return (array) unserialize($this->fields[$col]);
    }
    public function getBoolean($column=null)
    {
        if (null === $col = $this->checkColGet($column)) {
        	return null;
        }
        return (bool) $this->fields[$col];
    }
    public function getFloat($column=null)
    {
        if (null === $col = $this->checkColGet($column)) {
        	return null;
        }
        return (float) $this->fields[$col];
    }
    public function getInt($column=null)
    {
        if (null === $col = $this->checkColGet($column)) {
        	return null;
        }
        return (int) $this->fields[$col];
    }
    public function getString($column=null)
    {
        if (null === $col = $this->checkColGet($column)) {
        	return null;
        }
        return ($this->rtrimString ? rtrim($this->fields[$col]) : (string) $this->fields[$col]);
    }

    /**
     * Check if a given column in the current row exists
     * 
     */
    private function checkColGet($column=null)
    {
        $col = (is_int($column) ? $column - 1 : $column);
/*
        if (!array_key_exists($col, $this->fields)) {
            throw new Exception("Invalid resultset column: " . $col);
        }
*/        if (!array_key_exists($col, $this->fields)) {
            return null;
        }
        return $col;
    }

//---------------------------------------------------------------------------
    /**
     * Gets a row from the results array and adjusts the keys of the row's fields as required by $fetchmode
     * The row can come from 
     * 0: the $fields array or from 
     * 1: a row of the results array
     * The refreshed row is saved to the $fields array
     */
    private function refresh_keys(int $source, ?int $fetchmode=null)
    {
		$fetchmode = $fetchmode ?? $this->fetchmode;

		// Bail if for some reason we have an empty resultset
		if (empty($this->array)) return false;
		
		// Where is our fields data coming from?
		if ($source == 0) {
			// Get the row from the fields array
			$row = $this->fields;
		} else {
			// Make sure the cursor is pointing to a valid row in the results array
			if (!array_key_exists($this->cursor, $this->array)) {
				return false;
			}		
			// Get the row from the results array
			$row = $this->array[$this->cursor];
		}
		
		// Get the first row, for the keys
		$firstrow = reset($this->array);
		$keys = array_keys($firstrow);
		
		if ($fetchmode == PDO::FETCH_NUM) {
			// Flip the keys array to get numeric values
			$keys = array_flip($keys);
		} elseif ($fetchmode == PDO::FETCH_ASSOC) {
			// Nothing to do: the results array already has associative keys
		} else {
			// We don't support FETCH_BOTH for now
		}
		$row = array_combine($keys, $row);

		$this->fields = $row;
		return true;
    }

}
