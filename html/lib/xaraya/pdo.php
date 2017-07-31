<?php
/**
 * PDO wrapper class
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
class xarDB
{
    public static $count = 0;

    // Instead of the globals, we save our db info here.
    private static $firstDSN = null;
    private static $firstFlags = null;
    private static $connections = array();
    private static $tables = array();
    private static $prefix = '';

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
       
    public function __construct($dsn, $username, $password, $options)
    {
        try {
            parent::__construct($dsn, $username, $password, $options);
        } catch (Exception $e) {
            var_dump($e->getMessage());exit;
        }
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('xarPDOStatement', array($this)));
        $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function getDatabaseInfo()
    {
        if (null === $this->databaseInfo) {
            $databaseInfo = new DatabaseInfo($this);
            
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
        xarLog::message("DB: starting transaction", xarLog::LEVEL_INFO);
        // Only start a transaction of we need to
        if (!PDO::inTransaction())
            parent::beginTransaction();
        return true;
    }

    public function prepareStatement($string='')
    {
        if (substr(strtoupper($string),0,6) == "SELECT") {
            // This only works for MySQL !!
            $string .= " LIMIT ? OFFSET ?";
        }
        
        $this->queryString = $string;
        return parent::prepare($string);
    }
    
    public function qstr($string)
    {
        return "'".str_replace("'","\\'",$string)."'";
    }
    
    public function executeUpdate($string='')
    {
        xarLog::message("DB: Executing $string", xarLog::LEVEL_INFO);
        $stmt = $this->exec($string);
        if (substr(strtoupper($string),0,6) == "INSERT") {
            $this->last_id = $this->lastInsertId();
        }
        return $stmt;
    }
    
    public function Execute($string, $bindvars=array(), $flag=0)
    {
        xarLog::message("DB: Executing $string", xarLog::LEVEL_INFO);
        if (empty($flag)) $flag = PDO::FETCH_NUM;
        
        if (is_array($bindvars) && !empty($bindvars)) {
            $stmt = self::prepare($string);
            $stmt->setPDO($this);
            $result = $stmt->executeQuery($bindvars, $flag);
        } else {
            $stmt = $this->query($string, $flag);
            $result = new ResultSet($stmt, $flag);
        }
        if (substr(strtoupper($string),0,6) == "INSERT") {
            $this->last_id = $this->lastInsertId();
        }
        $this->row_count = $stmt->rowCount();
        return $result;
    }

    public function ExecuteQuery($string='', $flag=0)
    {
        xarLog::message("DB: Executing $string", xarLog::LEVEL_INFO);
        if (empty($flag)) $flag = PDO::FETCH_NUM;

        $stmt = $this->query($string);
        if (substr(strtoupper($string),0,6) == "INSERT") {
            $this->last_id = $this->lastInsertId();
        }
        $this->row_count = $stmt->rowCount();
        return new ResultSet($stmt, $flag);
    }
    
    public function SelectLimit($string='', $limit=0, $offset=0, $bindvars=array(), $flag=0)
    {
        if (empty($flag)) $flag = PDO::FETCH_NUM;
        $limit = empty($limit) ? 1000000 : $limit;

        // Lets try this the easy way
        // This only works for MySQL !!
        if (substr(strtoupper($string),0,6) == "SELECT") {
            $string .= " LIMIT $limit OFFSET $offset";
        }
        if (empty($bindvars)) {
            $stmt = $this->query($string, $flag);
            $result = new ResultSet($stmt, $flag);
        } else {
            // Prepare a SQL statement
            $stmt = self::prepare($string);
            
            // Pass this PDO object to the statement created
            $stmt->setPDO($this);
            
            // Execute the SQL statment and create a result set
            $result = $stmt->executeQuery($bindvars, $flag);
        }
        // Save the number of rows
        $this->row_count = $stmt->rowCount();
        // Save the limit and offset for future use
        $stmt->setLimit($limit);
        $stmt->setOffset($offset);
        
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
}

class xarPDOStatement extends PDOStatement
{
    private $pdo;
    private $limit    = 0;
    private $offset   = 0;
    private $bindvars = array();
    
    protected function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    public function setPDO($pdo)
    {
        $this->pdo = $pdo;
    }
    public function executeQuery($bindvars=array(), $flag=0)
    {
        xarLog::message("DB: Executing " . $this->pdo->queryString, xarLog::LEVEL_INFO);
        if (empty($flag)) $flag = PDO::FETCH_NUM;

        $index = 0;
        foreach ($bindvars as $bindvar) {
            $index++;
            if (is_int($bindvar)) {
                $this->bindValue($index, $bindvar, PDO::PARAM_INT);
            } elseif (is_bool($bindvar)) {
                $this->bindValue($index, $bindvar, PDO::PARAM_BOOL);
            } else {
                $this->bindValue($index, $bindvar, PDO::PARAM_STR);
            }
        }

        // This only works for MySQL !!
        if (substr(strtoupper($this->pdo->queryString),0,6) == "SELECT") {
            $index++;
            $limit = empty($this->limit) ? 1000000 : $this->limit;
            $this->bindValue($index, $limit, PDO::PARAM_INT);
            $index++;
            $offset = empty($this->offset) ? 0 : $this->offset;
            $this->bindValue($index, $offset, PDO::PARAM_INT);
        }
        
        // Run the query
        $d = parent::execute();
        
        if (substr(strtoupper($this->pdo->queryString),0,6) == "INSERT") {
            $this->pdo->last_id = $this->pdo->lastInsertId();
        }
        
        // Create a result set for the results
        $result = new ResultSet($this, $flag);
        // Save the bindvras
        $this->bindvars = $bindvars;
        return $result;
    }
    
    /* Be insistent and enforce types here */
    public function executeUpdate($bindvars=array(), $flag=0)
    {
        xarLog::message("DB: Executing " . $this->pdo->queryString, xarLog::LEVEL_INFO);
        $index = 0;
        foreach ($bindvars as $bindvar) {
            $index++;
            if (is_int($bindvar)) {
                $this->bindValue($index, $bindvar, PDO::PARAM_INT);
            } elseif (is_bool($bindvar)) {
                $this->bindValue($index, $bindvar, PDO::PARAM_BOOL);
            } else {
                $this->bindValue($index, $bindvar, PDO::PARAM_STR);
            }
        }
        // Run the query
        parent::execute();

        if (substr(strtoupper($this->pdo->queryString),0,6) == "INSERT") {
            $this->pdo->last_id = $this->pdo->lastInsertId();
        }
        
        // Save the bindvras
        $this->bindvars = $bindvars;
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
}

/**
 * DatabaseInfo class: holds the metainformation of the database
 *
 * PDO does not have much metadata, so we have to roll our own here
 *
 */
class DatabaseInfo extends Object
{
    private $pdo;
    private $tables;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getTable($name)
    {
        $pdotable = new PDOTable();
        
        // Table name is upper case by convention
        $name = strtoupper($name);
        
        // If we don't yet have this table's information, then get it
        if (!isset($this->tables[$name])) {
            $pdostatement = $this->pdo->query("SELECT * FROM $name LIMIT 1");
            for ($i = 0; $i < $pdostatement->columnCount(); $i++) {
                $column = $pdostatement->getColumnMeta($i);
                $this->tables[$name][$column['name']] = $column;
            }
        }
        
        $pdotable->setTable($this->tables[$name]);
        return $pdotable;
    }

    public function getPDO()
    {
        return $this->pdo;
    }
}

/**
 * PDOTable class: holds the metainformation of a database table
 *
 * PDO does not have much metadata, so we have to roll our own here
 *
 */
class PDOTable extends Object
{
    private $table;

    public function getColumns()
    {
        $columne = array();
        foreach ($this->table as $column) {
            $col = new PDOColumn($column);
            $columns[] = $col;
        }
        return $columns;
    }

    public function setTable($tableinfo)
    {
        $this->table = $tableinfo;
        return true;
    }
}

/**
 * PDOTable class: holds the metainformation of a database table
 *
 * PDO does not have much metadata, so we have to roll our own here
 *
 */
class PDOColumn extends Object
{
    private $column;

    public function __construct($column)
    {
        $this->column = $column;
        return true;
    }


    public function getType()
    {
        return $this->column['native_type'];
    }
    public function getPDOType()
    {
        return $this->column['pdo_type'];
    }
    public function getName()
    {
        return $this->column['name'];
    }
    public function getFlags()
    {
        return $this->column['flags'];
    }
    public function getTable()
    {
        return $this->column['table'];
    }
    public function getLength()
    {
        return $this->column['len'];
    }
    public function getPrecision()
    {
        return $this->column['precision'];
    }
}

/**
 * ResultSet class: holds the result of a query
 *
 * PDO does not have result sets, so we have to roll our own here
 *
 */
class ResultSet extends Object
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
    
    public function __construct($pdostatement, $flag=0,$dork=0)
    {
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
?>