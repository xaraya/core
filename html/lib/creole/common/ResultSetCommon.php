<?php
/*
 *  $Id: ResultSetCommon.php,v 1.9 2006/01/17 19:44:38 hlellelid Exp $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://creole.phpdb.org>.
 */

/**
 * This class implements many shared or common methods needed by resultset drivers.
 * 
 * This class may (optionally) be extended by driver classes simply to make it easier
 * to create driver classes.  This is also useful in the early stages of Creole development
 * as it means that API changes affect fewer files. As Creole matures/stabalizes having
 * a common class may become less useful, as drivers may have their own ways of doing things
 * (and we'll have a solid unit test framework to make sure drivers conform to the API
 * described by the interfaces).
 * 
 * The get*() methods in this class will format values before returning them. Note
 * that if they will return <code>null</code> if the database returned <code>NULL</code>
 * which makes these functions easier to use than simply typecasting the values from the
 * db. If the requested column does not exist than an exception (SQLException) will be thrown.
 * 
 * <code>
 * $rs = $conn->executeQuery("SELECT MAX(stamp) FROM event", ResultSet::FETCHMODE_NUM);
 * $rs->next();
 * 
 * $max_stamp = $rs->getTimestamp(1, "d/m/Y H:i:s");
 * // $max_stamp will be date string or null if no MAX(stamp) was found
 * 
 * $max_stamp = $rs->getTimestamp("max(stamp)", "d/m/Y H:i:s");
 * // will THROW EXCEPTION, because the resultset was fetched using numeric indexing
 * // SQLException: Invalid resultset column: max(stamp)
 * </code>
 * 
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.9 $
 * @package   creole.common
 */
abstract class ResultSetCommon {          
    
    /**
     * The fetchmode for this recordset.
     * @var int
     */
    protected $fetchmode;
    
    /**
     * DB connection.
     * @var Connection
     */
    protected $conn;
    
    /**
     * Resource identifier used for native result set handling.
     * @var resource
     */
    protected $result;

    /**
     * The current cursor position (row number). First row is 1. Before first row is 0.
     * @var int
     */
    protected $cursorPos = 0;
    
    /**
     * The current unprocessed record/row from the db.
     * @var array
     */
    // XARAYA MODIFICATION
    //protected $fields;
    public $fields;
    // END XARAYA MODIFICATION


    /**
     * A an assoc_array of all fields in the result set so we can easily test for their existence with isset(), which is much faster than array_key_exists()
     * @var assoc_array
     */
    protected $fieldsInResultSet;
    
    /**
     * Whether to convert assoc col case.
	 * @var boolean
     */
    protected $lowerAssocCase = false;
    
	/**
	 * Whether to apply rtrim() to strings.
	 * @var boolean
	 */
	protected $rtrimString = false;
	
    /**
     * Constructor.
     */
    public function __construct(Connection $conn, $result, $fetchmode = null)
    {
        $this->conn = $conn;
        $this->result = $result;             
        if ($fetchmode !== null) {
            $this->fetchmode = $fetchmode;
        } else {
            // XARAYA MODIFICATION
            //$this->fetchmode = ResultSet::FETCHMODE_ASSOC; // default
            $this->fetchmode = ResultSet::FETCHMODE_NUM; // default
            // END XARAYA MODIFICATION
        }
        $this->fieldsInResultSet = NULL;
        $this->lowerAssocCase = (($conn->getFlags() & Creole::COMPAT_ASSOC_LOWER) === Creole::COMPAT_ASSOC_LOWER);
		$this->rtrimString = (($conn->getFlags() & Creole::COMPAT_RTRIM_STRING) === Creole::COMPAT_RTRIM_STRING);
    }

    /**
     * Destructor
     *
     * Free db result resource.
     */
    public function __destruct()
    {
          $this->close();
    }
    
    /**
     *  Parse the fields in the result set to create a cache of all field names in the result set.
     *
     *  This is done so we can use isset() instead of array_key_exists() in the getXXX() functions b/c it's *much* faster.
     */
    protected function parseFields()
    {
        if ($this->fieldsInResultSet === NULL)
        {
            $this->fieldsInResultSet = array_flip(array_keys($this->fields));   // fastest way of creating non-null values for all keys
        }
    }
    
    /**
     * @see ResultSet::getIterator()
     */
    public function getIterator()
    {
        require_once 'creole/ResultSetIterator.php';
        return new ResultSetIterator($this);
    }
    
    /**
     * @see ResultSet::getResource()
     */
    public function getResource()
    {
        return $this->result;
    }
    
    /**
     * @see ResultSet::isLowereAssocCase()
     */
    public function isLowerAssocCase()
    {
        return $this->lowerAssocCase;
    }        
    
    /**
     * @see ResultSet::setFetchmode()
     */
    public function setFetchmode($mode)
    {
        $this->fetchmode = $mode;
    }
    
    /**
     * @see ResultSet::getFetchmode()
     */
    public function getFetchmode()
    {
        return $this->fetchmode;
    }                

    /**
     * @see ResultSet::previous()
     */
    public function previous()
    {
        // Go back 2 spaces so that we can then advance 1 space.
        $ok = $this->seek($this->cursorPos - 2);
        if ($ok === false) {
            $this->beforeFirst();
            return false;
        }        
        return $this->next();      
    }

    /**
     * @see ResultSet::isBeforeFirst()
     */
    public function relative($offset)
    {
        // which absolute row number are we seeking
        $pos = $this->cursorPos + ($offset - 1);
        $ok = $this->seek($pos);
                
        if ($ok === false) {
            if ($pos < 0) {
                $this->beforeFirst();
            } else {
                $this->afterLast();
            }
        } else {
            $ok = $this->next();
        }
        
        return $ok;
    }

    /**
     * @see ResultSet::absolute()
     */
    public function absolute($pos)
    {
        $ok = $this->seek( $pos - 1 ); // compensate for next() factor        
        if ($ok === false) {
            if ($pos - 1 < 0) {
                $this->beforeFirst();
            } else {
                $this->afterLast();
            }
        } else {
            $ok = $this->next();
        }        
        return $ok;
    }
    
    /**
     * @see ResultSet::first()
     */
    public function first()
    {
        if($this->cursorPos !== 0) { $this->seek(0); }
        return $this->next();
    }

    /**
     * @see ResultSet::last()
     */
    public function last()
    {
        if($this->cursorPos !==  ($last = $this->getRecordCount() - 1)) {
            $this->seek( $last );
        }
        return $this->next();
    }
    
    /**
     * @see ResultSet::beforeFirst()
     */
    public function beforeFirst()
    {
        $this->cursorPos = 0;
    }

    /**
     * @see ResultSet::afterLast()
     */
    public function afterLast()
    {
        $this->cursorPos = $this->getRecordCount() + 1;
    }

    /**
     * @see ResultSet::isAfterLast()
     */
    public function isAfterLast()
    {
        return ($this->cursorPos === $this->getRecordCount() + 1);
    }

    /**
     * @see ResultSet::isBeforeFirst()
     */
    public function isBeforeFirst()
    {
        return ($this->cursorPos === 0);
    }    

    /**
     * @see ResultSet::getCursorPos()
     */
    public function getCursorPos()
    {
        return $this->cursorPos;
    }
    
    /**
     * @see ResultSet::getRow()
     */
    public function getRow()
    {
        return $this->fields;
    }
    
    /**
     * @see ResultSet::get()
     */
    public function get($column)
    {
        if ($this->fetchmode == ResultSet::FETCHMODE_NUM) $column--;
        if (!isset($this->fieldsInResultSet[$column])) { throw new SQLException("Invalid resultset column: " . $column); }
        if ($this->fields[$column] === null) { return null; }
        return $this->fields[$column];
    }
    
    /**
     * @see ResultSet::getArray()
     */
    public function getArray($column) 
    {
        if ($this->fetchmode == ResultSet::FETCHMODE_NUM) $column--;
        if (!isset($this->fieldsInResultSet[$column])) { throw new SQLException("Invalid resultset column: " . $column); }
        if ($this->fields[$column] === null) { return null; }
        return (array) unserialize($this->fields[$column]);
    } 

    /**
     * @see ResultSet::getBoolean()
     */
    public function getBoolean($column) 
    {
        if ($this->fetchmode == ResultSet::FETCHMODE_NUM) $column--;
        if (!isset($this->fieldsInResultSet[$column])) { throw new SQLException("Invalid resultset column: " . $column); }
        if ($this->fields[$column] === null) { return null; }
        return (boolean) $this->fields[$column];
    }
            
    /**
     * @see ResultSet::getBlob()
     */
    public function getBlob($column) 
    {
        if ($this->fetchmode == ResultSet::FETCHMODE_NUM) $column--;
        if (!isset($this->fieldsInResultSet[$column])) { throw new SQLException("Invalid resultset column: " . $column); }
        if ($this->fields[$column] === null) { return null; }
        require_once 'creole/util/Blob.php';
        $b = new Blob();
        $b->setContents($this->fields[$column]);
        return $b;
    }    

    /**
     * @see ResultSet::getClob()
     */
    public function getClob($column) 
    {
        if ($this->fetchmode == ResultSet::FETCHMODE_NUM) $column--;
        if (!isset($this->fieldsInResultSet[$column])) { throw new SQLException("Invalid resultset column: " . $column); }
        if ($this->fields[$column] === null) { return null; }
        require_once 'creole/util/Clob.php';
        $c = new Clob();
        $c->setContents($this->fields[$column]);
        return $c;
    } 

    /**
     * @see ResultSet::getDate()
     */
    public function getDate($column, $format = '%x') 
    {
        if ($this->fetchmode == ResultSet::FETCHMODE_NUM) $column--;
        if (!isset($this->fieldsInResultSet[$column])) { throw new SQLException("Invalid resultset column: " . $column); }
        if ($this->fields[$column] === null) { return null; }
        $ts = strtotime($this->fields[$column]);        
        if ($ts === -1 || $ts === false) { // in PHP 5.1 return value changes to FALSE
            throw new SQLException("Unable to convert value at column " . $column . " to timestamp: " . $this->fields[$column]);
        }
        if ($format === null) {
            return $ts;
        }
        if (strpos($format, '%') !== false) {
            return strftime($format, $ts);
        } else {
            return date($format, $ts);
        }
    }    

    /**
     * @see ResultSet::getFloat()
     */
    public function getFloat($column) 
    {
        if ($this->fetchmode == ResultSet::FETCHMODE_NUM) $column--;
        if (!isset($this->fieldsInResultSet[$column])) { throw new SQLException("Invalid resultset column: " . $column); }
        if ($this->fields[$column] === null) { return null; }
        return (float) $this->fields[$column];
    }

    /**
     * @see ResultSet::getInt()
     */
    public function getInt($column) 
    {
        if ($this->fetchmode == ResultSet::FETCHMODE_NUM) $column--;
        if (!isset($this->fieldsInResultSet[$column])) { throw new SQLException("Invalid resultset column: " . $column); }
        if ($this->fields[$column] === null) { return null; }
        return (int) $this->fields[$column];
    }
       
    /**
     * @see ResultSet::getString()
     */
    public function getString($column) 
    {
        if ($this->fetchmode == ResultSet::FETCHMODE_NUM) $column--;
        if (!isset($this->fieldsInResultSet[$column])) { throw new SQLException("Invalid resultset column: " . $column); }
        if ($this->fields[$column] === null) { return null; }
		return ($this->rtrimString ? rtrim($this->fields[$column]) : (string) $this->fields[$column]);
    }
    
    /**
     * @see ResultSet::getTime()
     */
    public function getTime($column, $format = '%X') 
    {
        if ($this->fetchmode == ResultSet::FETCHMODE_NUM) $column--;
        if (!isset($this->fieldsInResultSet[$column])) { throw new SQLException("Invalid resultset column: " . $column); }
        if ($this->fields[$column] === null) { return null; }
        
        $ts = strtotime($this->fields[$column]);
        
        if ($ts === -1 || $ts === false) { // in PHP 5.1 return value changes to FALSE
            throw new SQLException("Unable to convert value at column " . (is_int($column) ? $column + 1 : $column) . " to timestamp: " . $this->fields[$column]);
        }
        if ($format === null) {
            return $ts;
        }        
        if (strpos($format, '%') !== false) {
            return strftime($format, $ts);
        } else {
            return date($format, $ts);
        }        
    }

    /**
     * @see ResultSet::getTimestamp()
     */
    public function getTimestamp($column, $format = 'Y-m-d H:i:s') 
    {
        if ($this->fetchmode == ResultSet::FETCHMODE_NUM) $column--;
        if (!isset($this->fieldsInResultSet[$column])) { throw new SQLException("Invalid resultset column: " . $column); }
        if ($this->fields[$column] === null) { return null; }
        
        $ts = strtotime($this->fields[$column]);
        if ($ts === -1 || $ts === false) { // in PHP 5.1 return value changes to FALSE
            throw new SQLException("Unable to convert value at column " . $column . " to timestamp: " . $this->fields[$column]);
        }
        if ($format === null) {
            return $ts;
        }
        if (strpos($format, '%') !== false) {
            return strftime($format, $ts);
        } else {
            return date($format, $ts);
        }        
    }  
    
    // XARAYA MODIFICATION
   function __get($propname)
   {
        switch($propname) {
            case 'EOF':
                // Used all over the place, probably needs to stay for a while
                return $this->isAfterLast();
                break;
            default:
                // We leave this in so any api migration error show up in a nice way
                throw new Exception("Unknown property accessed for connection");
        }
   }
    
   public function __call($method, $args) 
   {
        switch($method) {
            case 'MoveNext':
                // Used all over the place, prolly cant go for a while
                return $this->next();
                break;
            case 'GetRowAssoc':
                // We have to reget the current row in associative mode
                // Only seen in modules, prolly remove it here
                if ($this->getFetchMode() != ResultSet::FETCHMODE_ASSOC) {
                    $this->setFetchMode(ResultSet::FETCHMODE_ASSOC);
                    $this->next(); 
                    $this->previous();                
                }
                //bah
                return $this->getRow($args);
                break;
            case 'fetchRow':
                $res = $this->getRow();
                $this->next();
                return $res;
                break;
            case 'numRows':
            case 'RecordCount':
                // Used all over the place, migrated core over already,
                // Can't hurt to leave it in place
                return $this->getRecordCount();
                break;
            case 'FieldCount':
                $count = count($this->fields);
                return $count;
            default:
                // We leave this in so any api migration error show up in a nice way
                throw new Exception("Unknown method call $method for connection");
        }
    }
    // END XARAYA MODIFICATION
}
