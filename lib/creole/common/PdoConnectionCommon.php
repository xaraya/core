<?php
/*
 *  $Id: SQLiteConnection.php,v 1.15 2006/01/17 19:44:41 hlellelid Exp $
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
 
require_once 'creole/common/ConnectionCommon.php';

/**
 * SQLite implementation of Connection.
 * 
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Stig Bakken <ssb@fast.no> 
 * @author    Lukas Smith
 * @version   $Revision: 1.15 $
 * @package   creole.drivers.sqlite
 */ 
abstract class PdoConnectionCommon extends ConnectionCommon {   
    
    
    private $update_count = 0;
    
    /**
     *  a reference to the last result set if it is still open
     */
    private $open_result_set = false;
    
    private $open_result_set_ref = null;
    
    /**
     * @see Connection::connect()
     */
    function connect($dsninfo, $flags = 0, $pdo_dsn)
    {        
        if (!extension_loaded('pdo')) {
            throw new SQLException('pdo extension not loaded');
        }
        
        $file = $dsninfo['database'];
        
        $this->dsn = $dsninfo;
        $this->flags = $flags;
        
        $persistent = ($flags & Creole::PERSISTENT === Creole::PERSISTENT);
               
        // use persistent connections?
        $pdo_conn_flags = array();
        if( $persistent ) {
            $pdo_conn_flags[PDO::ATTR_PERSISTENT] = true;
        } else {
            $pdo_conn_flags[PDO::ATTR_PERSISTENT] = false;            
        }
        
        try {
            $conn = new PDO( $pdo_dsn, '', '', $pdo_conn_flags );
        } catch( PDOException $e ) {
            throw new SQLException("Unable to connect to SQLite database", $e->getMessage());
        }
        
        // set the error mode to throw exceptions so that we can catch them
        // and don't have to check the class error value after every method call
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // handle the case change
        if( ($flags & Creole::COMPAT_ASSOC_LOWER) === Creole::COMPAT_ASSOC_LOWER) {
            $conn->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        }
        
        $this->dblink = $conn;
    }   

    /**
     * @see Connection::close()
     */
    function close()
    {
        //PDO does not have a "close()" function, this will only destroy the
        // PDO object if all other references are non-existent
        $this->dblink = NULL;
        return null;
    }

    /**
     * @see Connection::executeQuery()
     */
    public function executeQuery($sql, $fetchmode = null, $rs_class)
    {    
        if( $this->openResultSet() ) {
            $this->handleOpenResultSet( );
        }
        
        $pdo_stmt = $this->executePdoQuery( $sql );
        
        $rs = new $rs_class($this, $pdo_stmt, $fetchmode);
        
        // note that we have an open resultset
        $this->openResultSet( $rs );
        
        return $rs;
    }
    
    public function handleOpenResultSet() {
        // include $sql in error for debugging
        trigger_error( "Implicitly closing a result set that is still open", E_USER_WARNING );
        $this->open_result_set_ref->closeCursor();
    }
    
    // TODO: multiple results sets "vollying" back and forth for being the 
    // open record set needs to be tested
    public function openResultSet( $val = null ) {
        if( $val !== null ) {
            if( is_object($val) ) {
                if( is_object( $this->open_result_set_ref ) ) {
                    $this->open_result_set_ref->closeCursor();
                }
                $this->open_result_set_ref = $val;
            } elseif( $val == false ) {
                $this->open_result_set_ref = null;
            }
            $this->open_result_set = $val ? true : false;
        }
        return $this->open_result_set;
    }
    
    /**
     * @returns the PDO statement
     */
    public function executePdoQuery( $sql ) {

        $this->lastQuery = $sql;
        try {
            $pdo_stmt = $this->dblink->prepare($this->lastQuery);
            $pdo_stmt->execute();
        } catch( PDOException $e ) {
            throw new SQLException('Could not execute query', $e->getMessage(), $this->lastQuery);
        }
        return $pdo_stmt;
    }
    
    /**
     * @see Connection::executeUpdate()
     */
    function executeUpdate($sql)
    {   
    
        if( $this->openResultSet() ) {
            $this->handleOpenResultSet( );
        }
        // reset the count
        $this->update_count = 0;
        
        $pdo_stmt = $this->executePdoQuery( $sql );
        try {
            $this->update_count = (int) $pdo_stmt->rowCount();
        } catch( PDOException $e ) {
            throw new SQLException('Could not get update count', $e->getMessage(), $this->lastQuery);
        }
        return $this->getUpdateCount();
    }
    
    /**
     * @see Connection::executeUpdate()
     */
     /*
    function executeUpdate2($sql)
    {   
        if( $this->openResultSet() ) {
            $this->handleOpenResultSet( $sql );
        }
        
        $this->lastQuery = $sql;
        try {
            $num_affected = $this->dblink->exec($this->lastQuery);
        } catch( PDOException $e ) {
            throw new SQLException('Could not execute update', $e->getMessage(), $this->lastQuery);
        }
        return (int) $num_affected;
    }*/
    
    /**
     * Start a database transaction.
     * @throws SQLException
     * @return void
     */
    protected function beginTrans()
    {
        try {
            $this->dblink->beginTransaction();
        } catch( PDOException $e ) {
            throw new SQLException('Could not begin transaction', $e->getMessage());
        }
    }
    
    /**
     * Commit the current transaction.
     * @throws SQLException
     * @return void
     */
    protected function commitTrans()
    {
        try {
            $this->dblink->commit();
        } catch( PDOException $e ) {
            throw new SQLException('Could not commit transaction', $e->getMessage());
        }
    }

    /**
     * Roll back (undo) the current transaction.
     * @throws SQLException
     * @return void
     */
    protected function rollbackTrans()
    {
        try {
            $this->dblink->rollBack();
        } catch( PDOException $e ) {
            throw new SQLException('Could not rollback transaction', $e->getMessage());
        }
    }
    
    public function getUpdateCount()
    {
        return $this->update_count;
    }
    
}
