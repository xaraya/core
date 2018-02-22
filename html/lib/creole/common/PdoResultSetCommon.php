<?php
/*
 *  $Id: SQLiteResultSet.php,v 1.9 2004/11/29 13:41:24 micha Exp $
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
 
require_once 'creole/common/ResultSetCommon.php';

/**
 * SQLite implementation of ResultSet class.
 *
 * SQLite supports OFFSET / LIMIT natively; this means that no adjustments or checking
 * are performed.  We will assume that if the lmitSQL() operation failed that an
 * exception was thrown, and that OFFSET/LIMIT will never be emulated for SQLite.
 * 
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.9 $
 * @package   creole.drivers.sqlite
 */
class PdoResultSetCommon extends ResultSetCommon{
    
    /**
     * Number of rows in resultset.
     *
     * @var int
     */
    protected $numRows = -1;
    
    /**
     * The PDO fetch_style to be used (PDO::FETCH_ASSOC or PDO::FETCH_NUM)
     */
    private $pdo_fetch_style;
    
    private $oldCursorPos = -1;
    
    private $in_cursor_recover = false;
    
    public function __construct(Connection $conn, $result, $fetchmode = null)
    {   
        
        parent::__construct($conn, $result, $fetchmode );
        
        $this->pdo_fetch_style = $this->getFetchMode() == ResultSet::FETCHMODE_ASSOC ? PDO::FETCH_ASSOC : PDO::FETCH_NUM;
        
        
    }
    
    /**
     * Gets optimized SQLiteResultSetIterator.
     * @return SQLiteResultSetIterator
     */
    public function getIterator()
    {   throw new SQLException('PdoResultSet::getIterator() not yet implimented');
        //require_once 'creole/drivers/pdosqlite/PdoSQLiteResultSetIterator.php';
        //return new PdoSQLiteResultSetIterator($this);
    }
     
    private function closedCursorRecover() {
        if( $this->oldCursorPos != -1 ) {
            // re execute the statement
            try {
                $this->result->execute();
            } catch( PDOException $e ) {
                throw new SQLException('Could not re-execute PDO statement', $e->getMessage());
            }
            $this->in_cursor_recover = true;
            $this->seek( $this->oldCursorPos );
            $this->oldCursorPos = -1;
            $this->in_cursor_recover = false;
            $this->conn->openResultSet( $this );
        }
    }
    
    /**
     * @see ResultSet::isBeforeFirst()
     */
    public function relative($offset)
    {
        $this->closedCursorRecover();
        return parent::relative($offset);
    }

    /**
     * @see ResultSet::absolute()
     */
    public function absolute($pos)
    {
        $this->closedCursorRecover();
        return parent::absolute($pos);  
    }
    
    /**
     * @see ResultSet::first()
     */
    public function first()
    {
        $this->closedCursorRecover();
        return parent::first(); 
    }

    /**
     * @see ResultSet::last()
     */
    public function last()
    {
        $this->closedCursorRecover();
        return parent::last(); 
    }
    
    /**
     * @see ResultSet::getCursorPos()
     */
    public function getCursorPos()
    {
        $this->closedCursorRecover();
        return parent::getCursorPos();
    }
           
    /**
     * @see ResultSet::seek()
     */ 
    function seek($rownum)
    {   //test_p( "cursor: {$this->cursorPos} seeking: $rownum"); 
        if ( $rownum < $this->cursorPos )
		{
            // this will effectively disable previous(), first() and some calls to relative() or absolute()
            throw new SQLException( 'PDO ResultSet is FORWARD-ONLY' );
        }
        
        $ok = true;
        // Emulate this until PDO cursors become more reliable
        while ( ($this->cursorPos < $rownum) && $ok )
		{
           $result = $this->_next();
           $ok = empty($result) ? false : true;
        }
        
        if( $ok ) {
            $this->cursorPos = $rownum;
            return true;
        } else {
            return false;
        }
    }
    
    private function _next() {
        try {
            $result = $this->result->fetch( $this->pdo_fetch_style );
            
            if( empty($result) ) {
                // We've advanced beyond end of recordset.
                $this->afterLast();
                
                // Tell the connection we are no longer open
                $this->conn->openResultSet( false );
                
                return false;
            }
        } catch( PDOException $e ) {
            throw new SQLException('Error fetching result', $e->getMessage());
        }
        
        // Advance cursor position
        $this->cursorPos++;
        return $result;
    }
    
    /**
     * @see ResultSet::next()
     */ 
    function next()
    {   
        $this->fields = $this->_next();
        
        return empty($this->fields) ? false : true;
    }

    /**
     * @see ResultSet::getRecordCount()
     * 
     * TODO: manipulate the SQL to do a count() to account for better perform-
     *       ance with large result sets
     */
    public function getRecordCount()
    {   
        // use the cached numRows value if possible
        if( $this->numRows == -1 ) {
            // get the sql query string from the PDO statement object associated 
            // with this result set.  We could use lastQuery from the connection, 
            // but we can not be sure that the connection's last query is the same
            // query that gave us our result set
            $sql = $this->result->queryString;
        
            $pdo_stmt = $this->conn->executePdoQuery( $sql );
            $this->numRows = count($pdo_stmt->fetchAll());
        }
        return $this->numRows;
    }
    
    public function close() {
        $this->closeCursor();
        unset($this->result);
        $this->fields = array();
    }
    /**
     * Simply empties array as there is no result free method for sqlite.
     * @see ResultSet::close()
     */
    public function closeCursor()
    {   
        $this->oldCursorPos = $this->cursorPos;
        $this->cursorPos = 0;
        $this->conn->openResultSet(false);
        if( !empty( $this->result ) && $this->result instanceof PDOStatement ) {
            $this->result->closeCursor();
        }
    }
}
