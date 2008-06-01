<?php
/*
 *  $Id: ConnectionCommon.php,v 1.5 2005/10/17 19:03:51 dlawson_mi Exp $
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
 * Class that contains some shared/default information for connections.  Classes may wish to extend this so
 * as not to worry about the sleep/wakeup methods, etc.
 *
 * In reality this class is not very useful yet, so there's not much incentive for drivers to extend this.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.5 $
 * @package   creole.common
 */
abstract class ConnectionCommon {

    // Constants that define transaction isolation levels.
    // [We don't have any code using these yet, so there's no need
    // to initialize these values at this point.]
    // const TRANSACTION_NONE = 0;
    // const TRANSACTION_READ_UNCOMMITTED = 1;
    // const TRANSACTION_READ_COMMITTED = 2;
    // const TRANSACTION_REPEATABLE_READ = 3;
    // const TRANSACTION_SERIALIZABLE = 4;

       /**
     * The depth level of current transaction.
     * @var int
     */
    protected $transactionOpcount = 0;

    /**
     * Stack of savepoint names used for nested transaction emulation.
     * @var array
     */
    protected $nestedTransactionSavepoints = array();
    
    /**
     * DB connection resource id.
     * @var resource
     */
    protected $dblink;

    /**
     * Array hash of connection properties.
     * @var array
     */
    protected $dsn;

    /**
     * Flags (e.g. Connection::PERSISTENT) for current connection.
     * @var int
     */
    protected $flags = 0;

    /* XARAYA MODIFICATION */
    // Adodb has a method on a connection(!!) for affected rows
    protected $affected_rows = 0;
    /* END XARAYA MODIFICATION */

    /**
     * This "magic" method is invoked upon serialize() and works in tandem with the __wakeup()
     * method to ensure that your database connection is serializable.
     *
     * This method returns an array containing the names of any members of your class
     * which need to be serialized in order to allow the class to re-connect to the database
     * when it is unserialized.
     *
     * <p>
     * Developers:
     *
     * Note that you cannot serialize resources (connection links) and expect them to
     * be valid when you unserialize.  For this reason, you must re-connect to the database in the
     * __wakeup() method.
     *
     * It's up to your class implimentation to ensure that the necessary data is serialized.
     * You probably at least need to serialize:
     *
     *  (1) the DSN array used by connect() method
     *  (2) Any flags that were passed to the connection
     *  (3) Possibly the autocommit state
     *
     * @return array The class variable names that should be serialized.
     * @see __wakeup()
     * @see DriverManager::getConnection()
     * @see DatabaseInfo::__sleep()
     */
    public function __sleep()
    {
        return array('dsn', 'flags');
    }

    /**
     * This "magic" method is invoked upon unserialize().
     * This method will re-connects to the database using the information that was
     * stored using the __sleep() method.
     * @see __sleep()
     */
    public function __wakeup()
    {
        $this->connect($this->dsn, $this->flags);
    }

    /**
     * @see Connection::getResource()
     */
    public function getResource()
    {
        return $this->dblink;
    }

    /**
     * @see Connection::getDSN()
     */
    public function getDSN() {
        return $this->dsn;
    }

    /**
     * @see Connection::getFlags()
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * Creates a CallableStatement object for calling database stored procedures.
     *
     * @param string $sql
     * @return CallableStatement
     */
    public function prepareCall($sql)
    {
        throw new SQLException("Current driver does not support stored procedures using CallableStatement.");
    }

    /**
     * Driver classes should override this if they support transactions.
     *
     * @return boolean
     */
    public function supportsNestedTrans()
    {
        return false;
    }

    /**
     * Begins a transaction (if supported).
     */
    public function begin()
    {
        if ($this->transactionOpcount === 0 || $this->supportsNestedTrans()) {
            $this->beginTrans();
        } elseif ($this->supportsSavepoints()) {
        	$savepointIdentifier = "creole_savepoint_".count($this->nestedTransactionSavepoints);
        	$this->nestedTransactionSavepoints[] = $savepointIdentifier;
        	$this->setSavepoint( $savepointIdentifier );
        }
        $this->transactionOpcount++;
        xarLogMessage("DB: starting transaction [".$this->transactionOpcount."]");
    }

    /**
     * Commits statements in a transaction.
     */
    public function commit()
    {
        if ($this->transactionOpcount > 0) {
            if ($this->transactionOpcount == 1 || $this->supportsNestedTrans()) {
                $this->commitTrans();
				xarLogMessage("DB: committed transaction [".$this->transactionOpcount."]");
            } elseif ($this->supportsSavepoints()) {
            	$savepointIdentifier = array_pop( $this->nestedTransactionSavepoints );
            	$this->releaseSavepoint( $savepointIdentifier );
			    xarLogMessage("DB: releasing savepoint of transaction [".$this->transactionOpcount."]");
            }
            $this->transactionOpcount--;
        }
    }

    /**
     * Rollback changes in a transaction.
     */
    public function rollback()
    {
        if ($this->transactionOpcount > 0) {
            if ($this->transactionOpcount == 1 || $this->supportsNestedTrans()) {
                $this->rollbackTrans();
            } elseif ($this->supportsSavepoints()) {
            	$savepointIdentifier = array_pop( $this->nestedTransactionSavepoints );
            	$this->rollbackToSavepoint( $savepointIdentifier );
				xarLogMessage("DB: Rolled back transaction [".$this->transactionOpcount."]");
            }
            $this->transactionOpcount--;
        }
    }

    /**
     * Checks if the current connection supports savepoints
     * 
     * Driver classes should override this if they support savepoints.
     * 
     * @return bool Does the connection support savepoints
     */
    protected function supportsSavepoints()
    {
    	return false;
    }    

    /**
     * Creates a new savepoint
     *
     * @param string $identifier Name of the savepoint to create
     */
    protected function setSavepoint( $identifier )
    {
    	throw new SQLException('This database driver doesn\'t support savepoints');
    }
    
    /**
     * Releases a savepoint
     *
     * @param string $identifier Name of the savepoint to release
     */
    protected function releaseSavepoint( $identifier )
    {
    	throw new SQLException('This database driver doesn\'t support savepoints');
    }
    
    /**
     * Rollback changes to a savepoint
     *
     * @param string $identifier Name of the savepoint to rollback to
     */
    protected function rollbackToSavepoint( $identifier )
    {
    	throw new SQLException('This database driver doesn\'t support savepoints');
    }
    
    /**
     * Enable/disable automatic commits.
     *
     * Pushes SQLWarning onto $warnings stack if the autocommit value is being changed mid-transaction. This function
     * is overridden by driver classes so that they can perform the necessary begin/end transaction SQL.
     *
     * If auto-commit is being set to TRUE, then the current transaction will be committed immediately.
     *
     * @param boolean $bit New value for auto commit.
     * @return void
     */
    public function setAutoCommit($bit)
    {
        if ($this->transactionOpcount > 0) {
            trigger_error("Changing autocommit in mid-transaction; committing " . $this->transactionOpcount . " uncommitted statements.", E_USER_WARNING);
        }

        if (!$bit) {
            $this->begin();
        }
        else {
            $this->commit();
        }
    }

    /**
     * Get auto-commit status.
     *
     * @return boolean
     */
    public function getAutoCommit()
    {
        return ($this->transactionOpcount == 0);
    }

    /**
     * Begin new transaction.
     * Driver classes should override this method if they support transactions.
     */
    protected function beginTrans()
    {
    }

    /**
     * Commit the current transaction.
     * Driver classes should override this method if they support transactions.
     */
    protected function commitTrans()
    {
    }

    /**
     * Roll back (undo) the current transaction.
     * Driver classes should override this method if they support transactions.
     */
    protected function rollbackTrans()
    {
    }

    // XARAYA MODIFICATION
    // to prevent changing all execute statements
    public function &Execute($sql,$bindvars = array(), $fetchmode = null)
    {
        xarLogMessage("DB: $sql");
        $stmt = $this->prepareStatement($sql);
        if($stmt) {
            if($this->isSelect($sql)) {
                $res = $stmt->executeQuery($bindvars,$fetchmode);
                if($res) {
                    // ADODB used to set the resultset on the first, doh!
                    $res->first();
                }
            } else {
                $res = $stmt->executeUpdate($bindvars);
                // Save it, for adodb compat for the the method Affected_Rows
                $this->affected_rows = $res;
                if($res == 0 ) $res=true;
            }
            if(!$res) {
                throw new SQLException("CREOLE: query $sql failed to execute");
            }
            return $res;
        }
    }

    public function &SelectLimit($sql,$limit=0 ,$offset=0 , $bindvars = array(),$fetchmode = null)
    {
        xarLogMessage("DB: $sql");
        $stmt = $this->prepareStatement($sql);
        $stmt->setLimit($limit);
        $stmt->setOffset($offset);
        $result = $stmt->executeQuery($bindvars, $fetchmode);
        $result->first();
        return $result;

    }

    private function isSelect($sql)
    {
        // is first word is SELECT, then return true, unless it's SELECT INTO ...
        // this doesn't, however, take comments into account ...
        $sql = trim($sql);
        return (stripos($sql, 'select') === 0 && stripos($sql, 'select into ') !== 0);
    }

    /* Create convenience wrappers, so the id's can be had on the connection directly */
    public function getNextId($tableName)
    {
        $idgen = $this->getIdGenerator();
        return $idgen->getNextId($tableName);
    }

    public function getLastId($tableName)
    {
        $idgen = $this->getIdGenerator();
        return $idgen->getLastId($tableName);
    }

    function __get($propname)
    {
        switch($propname) {
            // return the database type
            case 'databaseType':
                // This has no realistic equivalent in creole, probably leave it in
                return $this->dsn['phptype'];
                break;
            case 'hasTransactions':
                // all of em have, from the point of view of the callee
                return true;
                break;
            default:
                // We want to leave this in, so the migration errors show up nicely
                throw new Exception("Unknown property $propname accessed for connection");
        }
    }

    function __call($method, $args)
    {
        switch(strtolower($method)) {
            case 'qstr':
                // Used in a couple of places where bind variable replacement is less than trivial
                // (roles and dd only)
                // DOH! we dont want this
                return  "'".str_replace("'","\\'",$args[0])."'";
                break;
            case 'starttrans':
                return $this->begin();
                break;
            case 'completetrans':
                $this->commit();
                return true;
                break;
            case 'affected_rows':
                return $this->affected_rows;
                break;
            case 'genid':
                return $this->getNextId($args[0]);
                break;
            case 'po_insert_id':
                return $this->getLastId($args[0]);
                break;
            default:
                // We do want to leave this in, so the migration erros show up nicely
                throw new Exception("Unknown method call $method for connection");
        }
    }

    function &MetaTables($ttype=false,$showSchema=false,$mask=false)
    {
        $dbinfo = $this->getDatabaseInfo();
        $tables = $dbinfo->getTables();
        foreach ($tables as $table) {
            $tmp[$table->getName()]= $table->getName();
        }
        return $tmp;
    }

    /*
     * Some modules in xaraya use this to test whether a db execute operation has succeeded
     * value 0: succeeded
     * otherwise: error
     * Since we throw exceptions if we have an error, this just return 0
     * @todo remove when we can, only seen in modules
     */
    function ErrorNo() { return 0;  }
    // END XARAYA MODIFICATION

    /**
     * Returns false if connection is closed.
     * @return boolean
     */
    public function isConnected()
    {
        return !empty($this->dblink);
    }
}
