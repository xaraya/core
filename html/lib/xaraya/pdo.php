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
class xarDB_PDO extends xarObject
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
 */
    public static function newConn(array $args = null)
    {
        // Get database parameters
        $dsn = array('phptype'   => $args['databaseType'],
                     'hostspec'  => $args['databaseHost'],
                     'port'      => $args['databasePort'],
                     'username'  => $args['userName'],
                     'password'  => $args['password'],
                     'database'  => $args['databaseName'],
                     'encoding'  => $args['databaseCharset']);
        // Set flags
        $flags = array();
        $persistent = !empty($args['persistent']) ? true : false;
        if($persistent) $flags[]  = PDO::ATTR_PERSISTENT;
        // if code uses assoc fetching and makes a mess of column names, correct
        // this by forcing returns to be lowercase
        // <mrb> : this is not for nothing a COMPAT flag. the problem still lies
        //         in creating the database schema case sensitive in the first
        //         place. Unfortunately, that is just not portable.
        $flags[] = PDO::CASE_LOWER;

        try {
            $conn = xarDB::getConnection($dsn,$flags); // cached on dsn hash, so no worries
        } catch (Exception $e) {
            throw $e;
        }
        xarLog::message("New connection created, now serving " . self::$count . " connections", xarLog::LEVEL_INFO);
        return $conn;
    }
    /**
     * Get an array of database tables
     *
     * @return array array of database tables
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

    public static function configure($dsn, $flags = array(PDO::CASE_LOWER) , $prefix = 'xar')
    {
        $persistent = !empty($dsn['persistent']) ? true : false;
        if ($persistent) $flags[] = PDO::ATTR_PERSISTENT;

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
     * @return object database connection object
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

      // CHECKME: I've spent almost a day debuggin this when not assigning
      //          it first to a temporary variable before returning. 
      // The observed effect was that an exception did not occur when $index
      // whas 0 (the default case) in $connections and it didn't exist.
      // I believe this to be a PHP bug
      $conn = self::$connections[$index]; 
      return $conn;
    }

    public static function getConnection($dsn, $flags=array())
    {
    $dsn['phptype'] = 'mysql';
        // CHECKME: What about ports?
        $dsnstring  = $dsn['phptype'] . ':host=' . $dsn['hostspec'] . ';';
        if (!empty($dsn['port'])) $dsnstring .= 'port=' . $dsn['port'] . ";";
        $dsnstring .= 'dbname=' . $dsn['database'] . ";";
        $dsnstring .= 'charset=' . $dsn['encoding'] . ";";

        try {
            $conn = new xarPDO($dsnstring, $dsn['username'], $dsn['password'], $flags);
        } catch (PDOException $e) {
            throw $e;
        }

        self::setFirstDSN($dsn);
        self::setFirstFlags($flags);
        self::$connections[] =& $conn;
        self::$count++;
        return $conn;
    }

    /**
     * Get the PDO -> ddl type map
     *
     * @return array
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

class xarPDO extends PDO
{
    private $databaseInfo;

    public $databaseType  = "PDO";
    public $queryString   = '';
    public $row_count     = 0; 
    public $last_id       = null; 
    public $dblink        = null;

    public function __construct($dsn, $username, $password, $options)
    {
        try {
            parent::__construct($dsn, $username, $password, $options);
        } catch (Exception $e) {
            throw $e;
        }
        // Force PDO to prepare statements
        // CHECKME: setting this to false gives an error with some INSERT statements
        // (missing modules in modules_adminapi_regenerate)
        $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        // Show errors
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
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
        xarLog::message("xarPDO::begin: starting transaction", xarLog::LEVEL_INFO);
        // Only start a transaction of we need to
        if (!PDO::inTransaction())
            parent::beginTransaction();
        return true;
    }

    public function prepareStatement($string='')
    {
        $this->queryString = $string;
        $pdostmt = new xarPDOStatement($this);
        return $pdostmt;
    }
    
    public function qstr($string)
    {
        return "'".str_replace("'","\\'",$string)."'";
    }
    
    /**
     * Executes a SQL update and resturns the rows affected
     * 
     * @param string $string The query string
     * 
     * @return int $affected_rows the rows inserted, changed, dropped
     */
    public function executeUpdate($string='')
    {
        xarLog::message("xarPDO::executeUpdate: Executing $string", xarLog::LEVEL_INFO);
        try {
	        $affected_rows = $this->exec($string);
        } catch (Exception $e) {
        	throw $e;
        }
        if (substr(strtoupper($string),0,6) == "INSERT") {
            $this->last_id = $this->lastInsertId();
        }
        return $affected_rows;
    }
    
    /**
     * Executes a SQL query or update and resturn
     * 
     * @param string $string the query string
     * @param array $binvars the parameters to be inserted into the query
     * @param int $flag indicates the fetch mode for the results
     * 
     * @return object $resultset an object containing the results of the operation
     * 
     * Note:
     * - if bindvars are passed we generate a PDO statement and run that
     * - if no bindvars are passed but this is a SELECT, we run PDO's query method and return a PDO statement
     * - Otherwise (no bindvars and not a SELECT, we run PDO's exec method and generate an empty resultset
     */
    public function Execute($string, $bindvars=array(), $flag=0)
    {
        xarLog::message("xarPDO::Execute: Executing $string", xarLog::LEVEL_INFO);
        try {
			if (empty($flag)) $flag = PDO::FETCH_NUM;
				   
			if (is_array($bindvars) && !empty($bindvars)) {
				// Prepare a SQL statement
				$this->queryString = $string;
				$stmt = new xarPDOStatement($this);
				$result = $stmt->executeQuery($bindvars, $flag);
				return $result;
			} elseif (substr(strtoupper($string),0,6) == "SELECT") {
				$stmt = $this->query($string, $flag);
				$this->row_count = $stmt->rowCount();
				$result = new PDOResultSet($stmt, $flag);
				return $result;
			} else {
				$rows_affected = $this->exec($string);
				$this->row_count = $rows_affected;
				if (substr(strtoupper($string),0,6) == "INSERT") {
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
    public function ExecuteQuery($string='', $flag=0)
    {
        xarLog::message("xarPDO::executeQuery: Executing $string", xarLog::LEVEL_INFO);
        try {
			if (empty($flag)) $flag = PDO::FETCH_NUM;

			$stmt = $this->query($string);
			if (substr(strtoupper($string),0,6) == "INSERT") {
				$this->last_id = $this->lastInsertId();
			}
			$this->row_count = $stmt->rowCount();
			return new PDOResultSet($stmt, $flag);
        } catch (Exception $e) {
        	throw $e;
        }
    }
    
    public function SelectLimit($string='', $limit=0, $offset=0, $bindvars=array(), $flag=0)
    {
        if (empty($flag)) $flag = PDO::FETCH_NUM;
        $limit = empty($limit) ? 1000000 : $limit;
        
        // TODO: better type testing?
        $limit = $limit < 0 ? -1 : (int)$limit;
        $offset = $offset < 0 ? 0 : (int)$offset;

        // Lets try this the easy way
        // This only works for MySQL !!
        if (substr(strtoupper($string),0,6) == "SELECT") {
            // Only dd limit and offset if limit is positive
            if ($limit > 0) {
                $string .= " LIMIT ?";
                $bindvars[] = $limit;
                $string .= " OFFSET ?";
                $bindvars[] = $offset;
            }
        }
        xarLog::message("xarPDO::SelectLimit: Executing $string", xarLog::LEVEL_INFO);
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

    public function PO_Insert_ID($table=null, $field=null)
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
}

class xarPDOStatement extends xarObject
{
    private $pdo;
    private $pdostmt;
    private $limit     = 0;
    private $offset    = 0;
    private $haslimits = false;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->prepare($this->pdo->queryString);
        return true;
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

    public function executeQuery($bindvars=array(), $flag=0)
    {
        xarLog::message("xarPDOStatement::executeQuery: Preparing " . $this->pdo->queryString, xarLog::LEVEL_INFO);
        if (empty($flag)) $flag = PDO::FETCH_NUM;

        // We need to check whether we still have to add limit and offset
        // This only works for MySQL !!
        if (substr(strtoupper($this->pdo->queryString),0,6) == "SELECT" && ($this->limit > 0 || $this->offset > 0) && !$this->haslimits) {
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
        xarLog::message("xarPDOStatement::executeQuery: Executing " . $this->pdo->queryString, xarLog::LEVEL_INFO);
        try {
            $success = $this->pdostmt->execute();
        } catch (Exception $e) {
        	throw $e;
        }
        
        // If this is a SELECT, create a result set for the results
        if (substr(strtoupper($this->pdo->queryString),0,6) == "SELECT") {
            $result = new PDOResultSet($this, $flag);
            // Save the bindvars
            $this->bindvars = $bindvars;
            return $result;
        }
        
        // If this is an INSERT, get the last inserted ID and return
        if (substr(strtoupper($this->pdo->queryString),0,6) == "INSERT") {
            $this->pdo->last_id = $this->pdo->lastInsertId();
            return true;
        }

        // Anything else: just return for now
        return true;
    }

    /**
     * Prepares and executes a SQL update (INSERT, UPDATE, or DELETE) and resturns the rows affected
     * 
     * @param array $bindvars the parameters to be inserted into the query
     * @param int $flag indicates the fetch mode for the results
     * 
     * @return int $affected_rows the rows inserted, changed, dropped
     */
    /* Be insistent and enforce types here */
    public function executeUpdate($bindvars=array(), $flag=0)
    {
        xarLog::message("xarPDOStatement::executeUpdate: Preparing " . $this->pdo->queryString, xarLog::LEVEL_INFO);

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

        xarLog::message("xarPDOStatement::executeUpdate: Executing " . $this->pdo->queryString, xarLog::LEVEL_INFO);
        try {
            $success = $this->pdostmt->execute();
        } catch (Exception $e) {
            throw $e;
        }      

        if (substr(strtoupper($this->pdo->queryString),0,6) == "INSERT") {
            $this->pdo->last_id = $this->pdo->lastInsertId();
        }
        
        // Save the bindvars
        $this->bindvars = $bindvars;

        try {
            $rows_affected = (int) $this->pdostmt->rowCount();
        } catch( PDOException $e ) {
            throw new PDOException('Could not get update count', $e->getMessage(), $this->pdo->queryString);
        }
        return $rows_affected;
    }

   // Wrappers for the PDOStatement methods
    public function fetchAll($flags)
    {
        if ($this->pdostmt == null) throw new PDOException('No PDOStatement object');
        return $this->pdostmt->fetchAll($flags);
    }
    public function fetch($flags)
    {
        if ($this->pdostmt == null) throw new PDOException('No PDOStatement object');
        return $this->pdostmt->fetch($flags);
    }
    public function rowCount()
    {
        if ($this->pdostmt == null) throw new PDOException('No PDOStatement object');
        return $this->pdostmt->rowCount();
    }
    public function columnCount()
    {
        if ($this->pdostmt == null) throw new PDOException('No PDOStatement object');
        return $this->pdostmt->columnCount();
    }

    private function applyLimit(&$sql, $offset, $limit)
    {
        if ( $limit > 0 ) {
            $sql .= " LIMIT " . ($offset > 0 ? $offset . ", " : "") . $limit;
        } else if ( $offset > 0 ) {
            $sql .= " LIMIT " . $offset . ", 18446744073709551615";
        }
    }
}

/**
 * DatabaseInfo class: holds the metainformation of the database
 *
 * PDO does not have much metadata, so we have to roll our own here
 *
 */
class PDODatabaseInfo extends xarObject
{
    private $pdo;
    private $tables;

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
     * @return array
     */
    public function getTables()
    {
        if (!$this->tablesLoaded)
            $this->initTables();
        return $this->tables;
    }

    public function getTable($name)
    {
        if (!$this->tablesLoaded)
            $this->initTables();
        
        $uppername = strtoupper($name);
        if (!isset($this->tables[$uppername])) return null;
        return $this->tables[$uppername];
    }

    /**
     * @return void
     * @throws PDOException
     */
    private function initTables()
    {
        //$sql = "SELECT name FROM sqlite_master WHERE type='table' UNION ALL SELECT name FROM sqlite_temp_master WHERE type='table' ORDER BY name;";
        // get the list of all tables
        $sql = "SHOW TABLES";
        try {
            $pdostatement = $this->pdo->query($sql);
        } catch (PDOException $e) {
            throw new PDOException('Could not list tables', $e->getMessage(), $sql);
        }
        while ($row = $pdostatement->fetch()) {
            $thistable = $this->initTable($row[0]);
            $this->tables[strtoupper($row[0])] = $thistable;
        }
        $this->tablesLoaded = true;
        return true;
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
        if (!$this->columnsLoaded)
            $this->initColumns();
        return $this->columns;
    }

    public function getPrimaryKey()
    {
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
        if (!empty($key_column)) return $key_column;
        return false;
    }
    
    public function setTableName($name='')
    {
        $this->name = $name;
        return true;
    }

    public function setTableColumns($columns=array())
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
            throw new PDOException(xarML('Could not initialize table columns with: #(1)', $sql));
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
        return true;
    }
}

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
    
    public  $isAutoIncrement;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        return true;
    }
    public function setData($columndata=array())
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
    public function isAutoIncrement()
    {
        return $this->isAutoIncrement === true;
    }
    public function getColumns()
    {
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
                throw new PDOException(xarML('Could not get default value for column #(1) with #(2)', $this->getName(), $sql));
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

/**
 * ResultSet class: holds the result of a query
 *
 * PDO does not have result sets, so we have to roll our own here
 *
 */
class PDOResultSet extends xarObject
{
    const FETCHMODE_ASSOC = PDO::FETCH_ASSOC;
    const FETCHMODE_NUM   = PDO::FETCH_NUM;
    const EOF             = 0;
    
    private $pdostatement;
    private $fetchflag;
    private $valid  = true;
    private $array  = array();

	protected $rtrimString = false;

    public $cursor  = -1;
    public $fields  = array();
    
    public function __construct($pdostatement=null, $flag=0)
    {
        // We may not have a PDOSTatment
        if ($pdostatement==null) return $this;

        $this->fetchflag = empty($flag) ? self::FETCHMODE_NUM : $flag;
        $this->pdostatement = $pdostatement;
        $this->array = $this->pdostatement->fetchAll($this->fetchflag);
        $this->EOF = count($this->array) === 0;
        // @todo: This is an odd Creole legacy. Remove instances of calling $resilt->fields without next() first
        if (!empty($this->array)) $this->fields = reset($this->array);
    }
    
    public function close()
    {
        $this->pdostatement = null;
    }
   
    function current()  {   
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

    function next() {   
        $this->cursor++;
        $next = $this->getRow();
        $valid = ($next === false) ? false : true;
        if ($this->isAfterLast()) $this->EOF = true;
        return $valid;
    }
    // @todo Remove this in the code
    function MoveNext() {   
        return $this->next();
    }
    
    function getRow() {   
        if (empty($this->array[$this->cursor])) {
                return false;
            $row = $this->pdostatement->fetch($this->fetchflag);
            if (empty ($row)) {
                return false;
            } else {
                $this->array[$this->cursor] = $row;
                $this->fields = $row;
                return $this->fields;
            }
        } else {
            $this->fields = $this->array[$this->cursor];
            return $this->fields;
        }
    }
 
    function key()    {return $this->cursor;}
    function valid()  {return $this->valid;}
    function rewind() {$this->cursor = 0;}
    function first()  {$this->rewind(); return $this->getRow();}
    function getall() {return $this->array;}
    
    // Two of these functions is one too many
    public function RecordCount(){
        return $this->getRecordCount();
    }
    public function getRecordCount(){
        return count($this->array);
    }

    public function setFetchMode($flag)
    {
        if ($this->fetchflag == $flag) return true;
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
        if (!array_key_exists($col, $this->fields)) { throw new Exception("Invalid resultset column: " . $column); }
        if ($this->fields[$col] === null) { return null; }
        return (array) unserialize($this->fields[$col]);
    } 
    public function getBoolean($column) 
    {
        $col = (is_int($column) ? $column - 1 : $column);
        if (!array_key_exists($col, $this->fields)) { throw new Exception("Invalid resultset column: " . $column); }
        if ($this->fields[$col] === null) { return null; }
        return (boolean) $this->fields[$col];
    }
    public function getFloat($column) 
    {
        $col = (is_int($column) ? $column - 1 : $column);
        if (!array_key_exists($col, $this->fields)) { throw new Exception("Invalid resultset column: " . $column); }
        if ($this->fields[$col] === null) { return null; }
        return (float) $this->fields[$col];
    }
    public function getInt($column) 
    {
        $col = (is_int($column) ? $column - 1 : $column);
        if (!array_key_exists($col, $this->fields)) { throw new Exception("Invalid resultset column: " . $column); }
        if ($this->fields[$col] === null) { return null; }
        return (int) $this->fields[$col];
    }
    public function getString($column) 
    {
        $col = (is_int($column) ? $column - 1 : $column);
        if (!array_key_exists($col, $this->fields)) { throw new Exception("Invalid resultset column: " . $column); }
        if ($this->fields[$col] === null) { return null; }
		return ($this->rtrimString ? rtrim($this->fields[$col]) : (string) $this->fields[$col]);
    }

    public function getStatement()
    {
        return $this->pdostatement;
    }
}
