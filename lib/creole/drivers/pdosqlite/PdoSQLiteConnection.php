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
 
require_once 'creole/Connection.php';
require_once 'creole/common/PdoConnectionCommon.php';

/**
 * SQLite implementation of Connection.
 * 
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Stig Bakken <ssb@fast.no> 
 * @author    Lukas Smith
 * @version   $Revision: 1.15 $
 * @package   creole.drivers.sqlite
 */ 
class PdoSQLiteConnection extends PdoConnectionCommon implements Connection {   
    
    /**
     * @see Connection::connect()
     */
    function connect($dsninfo, $flags = 0)
    {        
        if (!extension_loaded('pdo_sqlite')) {
            throw new SQLException('pdo_sqlite extension not loaded');
        }
        
        $file = $dsninfo['database'];
        
        if ($file === null) {
            throw new SQLException("No SQLite database specified.");
        }
        
        $mode = (isset($dsninfo['mode']) && is_numeric($dsninfo['mode'])) ? $dsninfo['mode'] : 0644;
        
        if ($file != ':memory:') {
            
            if (!file_exists($file)) {
                if( !@touch($file) ) {
                    throw new SQLException("Unable to create SQLite database.  Check parent folder permissions.");
                }
                
                chmod($file, $mode);
                
                if (!file_exists($file)) {
                    throw new SQLException("Unable to create SQLite database.");
                }
            }
            if (!is_file($file)) {
                throw new SQLException("Unable to open SQLite database: not a valid file.");
            }
            if (!is_readable($file)) {
                throw new SQLException("Unable to read SQLite database.");
            }
        }
        
        // create the PDO DSN for the sqlite version requested
        $pdo_dsn = $dsninfo['phptype'] == 'pdosqlite2' ? 'sqlite2' : 'sqlite';
        
        // add the file name to the PDO DSN
        $pdo_dsn .= ':'.$dsninfo['database'];
        
        parent::connect( $dsninfo, $flags, $pdo_dsn );
        
        // make sure sqlite does not give long column names
        $this->executeQuery('PRAGMA full_column_names=0');
        $this->executeQuery('PRAGMA short_column_names=1');
    }   

    /**
     * @see Connection::getDatabaseInfo()
     */
    public function getDatabaseInfo()
    {
        require_once 'creole/drivers/pdosqlite/metadata/PdoSQLiteDatabaseInfo.php';
        return new PdoSQLiteDatabaseInfo($this);
    }
    
     /**
     * @see Connection::getIdGenerator()
     */
    public function getIdGenerator()
    {
        require_once 'creole/drivers/pdosqlite/PdoSQLiteIdGenerator.php';
        return new PdoSQLiteIdGenerator($this);
    }
    
    /**
     * @see Connection::prepareStatement()
     */
    public function prepareStatement($sql) 
    {
        require_once 'creole/drivers/pdosqlite/PdoSQLitePreparedStatement.php';
        return new PdoSQLitePreparedStatement($this, $sql);
    }
    
    /**
     * @see Connection::prepareCall()
     */
    public function prepareCall($sql) {
        throw new SQLException('SQLite does not support stored procedures using CallableStatement.');        
    }
    
    /**
     * @see Connection::createStatement()
     */
    public function createStatement()
    {
        require_once 'creole/drivers/pdosqlite/PdoSQLiteStatement.php';
        return new PdoSQLiteStatement($this);
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
     * @see Connection::applyLimit()
     */
    public function applyLimit(&$sql, $offset, $limit)
    {
        if ( $limit > 0 ) {
            $sql .= " LIMIT " . $limit . ($offset > 0 ? " OFFSET " . $offset : "");
        } elseif ( $offset > 0 ) {
            $sql .= " LIMIT -1 OFFSET " . $offset;
        }
    } 

    /**
     * @see Connection::executeQuery()
     */
    public function executeQuery($sql, $fetchmode = null)
    {    
        
        require_once 'creole/drivers/pdosqlite/PdoSQLiteResultSet.php';
        return parent::executeQuery( $sql, $fetchmode, 'PdoSQLiteResultSet');
    }
    
}
