<?php

/*
 *  $Id: MSSQLConnection.php,v 1.25 2005/10/17 19:03:51 dlawson_mi Exp $
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
require_once 'creole/common/ConnectionCommon.php';
include_once 'creole/drivers/mssql/MSSQLResultSet.php';

/**
 * MS SQL Server implementation of Connection.
 * 
 * If you have trouble with BLOB / CLOB support
 * --------------------------------------------
 * 
 * You may need to change some PHP ini settings.  In particular, the first two settings
 * set the text size to maximum which should get around issues with truncated data.
 * The third rectifies an issue with losing the seconds part of a DATETIME column
 * <code>
 *  ini_set('mssql.textsize', 2147483647);
 *  ini_set('mssql.textlimit', 2147483647);
 *  ini_set('mssql.datetimeconvert', 0);
 * </code>
 * We do not set these by default (anymore) because they do not apply to cases where MSSQL
 * is being used w/ FreeTDS.
 * 
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Stig Bakken <ssb@fast.no> 
 * @author    Lukas Smith
 * @version   $Revision: 1.25 $
 * @package   creole.drivers.mssql
 */ 
class MSSQLConnection extends ConnectionCommon implements Connection {        
    
    /** Current database (used in mssql_select_db()). */
    private $database;
    
    /**
     * @see Connection::connect()
     */
    function connect($dsninfo, $flags = 0)
    {                
        if (!extension_loaded('mssql') && !extension_loaded('sybase') && !extension_loaded('sybase_ct')) {
            throw new SQLException('mssql extension not loaded');
        }

        $this->dsn = $dsninfo;
        $this->flags = $flags;
                
        $persistent = ($flags & Creole::PERSISTENT === Creole::PERSISTENT);

        $user = $dsninfo['username'];
        $pw = $dsninfo['password'];
        $dbhost = $dsninfo['hostspec'] ? $dsninfo['hostspec'] : 'localhost';
		
		if (PHP_OS == "WINNT" || PHP_OS == "WIN32") {
            $portDelimiter = ",";
        } else {
            $portDelimiter = ":";
        }
       
        if(!empty($dsninfo['port'])) {
                $dbhost .= $portDelimiter.$dsninfo['port'];
        } else {
                $dbhost .= $portDelimiter.'1433';
        }
		
        $connect_function = $persistent ? 'mssql_pconnect' : 'mssql_connect';

        if ($dbhost && $user && $pw) {
            $conn = @$connect_function($dbhost, $user, $pw);
        } elseif ($dbhost && $user) {
            $conn = @$connect_function($dbhost, $user);
        } else {
            $conn = @$connect_function($dbhost);
        }
        if (!$conn) {
            throw new SQLException('connect failed', mssql_get_last_message());
        }
        
        if ($dsninfo['database']) {
            if (!@mssql_select_db($dsninfo['database'], $conn)) {
                throw new SQLException('No database selected');               
            }
            
            $this->database = $dsninfo['database'];
        }
        
        $this->dblink = $conn;        
    }    
    
    /**
     * @see Connection::getDatabaseInfo()
     */
    public function getDatabaseInfo()
    {
        require_once 'creole/drivers/mssql/metadata/MSSQLDatabaseInfo.php';
        return new MSSQLDatabaseInfo($this);
    }
    
     /**
     * @see Connection::getIdGenerator()
     */
    public function getIdGenerator()
    {
        require_once 'creole/drivers/mssql/MSSQLIdGenerator.php';
        return new MSSQLIdGenerator($this);
    }
    
    /**
     * @see Connection::prepareStatement()
     */
    public function prepareStatement($sql) 
    {
        require_once 'creole/drivers/mssql/MSSQLPreparedStatement.php';
        return new MSSQLPreparedStatement($this, $sql);
    }
    
    /**
     * @see Connection::createStatement()
     */
    public function createStatement()
    {
        require_once 'creole/drivers/mssql/MSSQLStatement.php';
        return new MSSQLStatement($this);
    }
    
    /**
     * Since MSSQL doesn't support this method natively, the SQL is modified to use
     * nested queries to attain the same result.
     *
     * @param string &$sql The query that will be modified.
     * @param int $offset
     * @param int $limit
     * @return void
     * @throws SQLException - if unable to modify query for any reason.
     */
    public function applyLimit(&$sql, $offset, $limit)
    {
        /*
        Offsets and limits can be done with nested sql using TOP

        SELECT * FROM (
            SELECT TOP x * FROM (
                SELECT TOP y fields
                FROM table
                WHERE conditions
                ORDER BY table.field  ASC) as foo
            ORDER by field DESC) as bar
        ORDER by field ASC

        x is limit and y is x+offset
        Note: see special case where limit is 0.
        */

        // obtain the original select statement
        preg_match('/\A(.*)select(.*)from/si',$sql,$select_segment);
        if(count($select_segment>0)) {
            $original_select = $select_segment[0];
        } else {
            // not a select query, nothing further to do
            return;
    }
        $modified_select = substr_replace($original_select, null, stristr($original_select,'SELECT') , 6 );
    
        // remove the original select statement
        $sql = str_replace($original_select , null, $sql);

        // obtain the original order by clause, or create one if there isn't one
        preg_match('/order by(.*)\Z/si',$sql,$order_segment);
        if(count($order_segment)>0) {
            $order_by = $order_segment[0];
        } else {
            // no order by clause, if there are columns we can attempt to sort by the columns in the select statement
            $select_items = split(',',trim(substr($modified_select,0,strlen($modified_select)-4)));
            if(count($select_items)>0) {
                $item_number = 0;
                $order_by = null;
                while($order_by === null && $item_number<count($select_items)) {
                    if(!strstr($select_items[$item_number],'*')) {
                        if (strstr($select_items[$item_number],'(')) {
                            // aggregate function used in field, if the field is named with AS, use it
                            //  if a name is not given, assign one for use as ORDER BY field
                            $aggregateFieldName = array();
                            //preg_match('/ as (.*)\Z/si',$select_items[$item_number],$aggregateFieldName);
                            if (count($aggregateFieldName) == 0) {
                                $select_items[$item_number].=' AS _creole_order_field';
                                $aggregateFieldName = array('_creole_order_field');
                            }
                            $order_by = 'ORDER BY ' . $aggregateFieldName[0] . ' ASC';
                        } else {
                            $order_by = 'ORDER BY ' . $select_items[$item_number] . ' ASC';
                        }
                    }
                    $item_number++;
                }
            }
            // since the select has possibly had a name added to a field, regenerate the select
            $modified_select = ' '.join(', ', $select_items).' FROM';
            if($order_by === null) {
                // no valid columns were found in the select statement (SELECT *), get a list of fields from the db
                $fieldSql = 'SELECT TOP 1 '.$modified_select.$sql;
                $fieldStmt = $this->prepareStatement($fieldSql);
                $fieldRs = $fieldStmt->executeQuery();
                $fieldRs->next();
                $fields = array_keys($fieldRs->getRow());
                // in this case, there is always at least one field
                $order_by = 'ORDER BY ' . $fields[0] . ' ASC';
            }
            $sql.= ' '.$order_by;
        }

        // modify the sort order for paging
        $inverted_order = '';
        $order_columns = split(',',str_ireplace('order by ','',$order_by));
        $original_order_by = $order_by;
        $order_by = '';
        foreach($order_columns as $column) {
            // strip "table." from order by columns
            $column = array_reverse(split("\.",$column));
            $column = $column[0];

            // commas if we have multiple sort columns
            if(strlen($inverted_order)>0){
                $order_by.= ', ';
                $inverted_order.=', ';
            }

            // put together order for paging wrapper
            if(stristr($column,' desc')) {
                $order_by .= $column;
                $inverted_order .= str_ireplace(' desc',' ASC',$column);
            } elseif(stristr($column,' asc')) {
                $order_by .= $column;
                $inverted_order .= str_ireplace(' asc',' DESC',$column);
            } else {
                $order_by .= $column;
                $inverted_order .= $column .' DESC';
            }
        }
        $order_by = 'ORDER BY ' . $order_by;
        $inverted_order = 'ORDER BY ' . $inverted_order;

        // build the query
        $modified_sql = "";
        if ( $limit > 0 ) {
            $modified_sql = 'SELECT * FROM (';
            $modified_sql.= 'SELECT TOP '.$limit.' * FROM (';
            $modified_sql.= 'SELECT TOP '.($limit+$offset).' '.$modified_select.$sql;
            $modified_sql.= ') OffsetSet '.$inverted_order.') LimitSet '.$order_by;
        } else {
            // For the case when the limit is 0, the idea is to return the entire recordset minus the offset
            $countSql = count($order_segment)>0 ? str_replace($order_segment[0] , null, $sql) : $sql;
            $countStmt = $this->prepareStatement("SELECT COUNT(*) FROM $countSql");
            $countRs = $countStmt->executeQuery(ResultSet::FETCHMODE_NUM);
            $countRs->next();
            $rowCount = $countRs->getInt(1);
            $modified_sql = 'SELECT * FROM (';
            $modified_sql.= 'SELECT TOP '.($rowCount-$offset).' * FROM (';
            $modified_sql.= 'SELECT TOP 100 PERCENT '.$modified_select.$sql;
            $modified_sql.= ') OffsetSet '.$inverted_order.') LimitSet '.$order_by;
        }
        $sql = $modified_sql;
    }
    
    /**
     * @see Connection::close()
     */
    function close()
    {
        $ret = @mssql_close($this->dblink);
        $this->dblink = null;
        return $ret;
    }
    
    /**
     * @see Connection::executeQuery()
     */
    function executeQuery($sql, $fetchmode = null)
    {            
        $this->lastQuery = $sql;
        if (!@mssql_select_db($this->database, $this->dblink)) {
            throw new SQLException('No database selected');
        }       
        $result = @mssql_query($sql, $this->dblink);
        if (!$result) {
            throw new SQLException('Could not execute query', mssql_get_last_message());
        }
        return new MSSQLResultSet($this, $result, $fetchmode);
    }

    /**
     * @see Connection::executeUpdate()
     */
    function executeUpdate($sql)
    {    
        
        $this->lastQuery = $sql;
        if (!mssql_select_db($this->database, $this->dblink)) {
            throw new SQLException('No database selected');
        }
        // XARAYA modification
        // We got to determine the table here and set identity insert to on for it
        // FIXME: this sucks
        $errMsgs = array();
        $tmpSql = str_replace('(',' (', $sql);
        $queryParts = preg_split("/[ ]+/", $tmpSql,4, PREG_SPLIT_NO_EMPTY);
        $stmtType = trim(strtolower($queryParts[0]));
        switch ($stmtType) {
            case 'update':
                $tablename = trim($queryParts[1]);
                break;
            case 'insert':
                $tablename = trim($queryParts[2]);
                break;
            default:
                $tablename = '';
        }
        // make sure we can insert into an identity column
        if($tablename != '') {
            $res = @mssql_query("SET IDENTITY_INSERT $tablename ON", $this->dblink);
            if(!$res) {
                // Dont except just yet, the table could have no identity column,
                $tablename ='';
                $errMsgs[] = mssql_get_last_message();
            }
        }
        
        $result = @mssql_query($sql, $this->dblink);
        if (!$result) {
            throw new SQLException('Could not execute update', mssql_get_last_message(), $sql);
        }
        
        if($tablename != '') {
            $res = mssql_query("SET IDENTITY_INSERT $tablename OFF", $this->dblink);
            if (!$res) {
                throw new SQLException('Could not unlock table for identity insert', mssql_get_last_message(), $sql);
            }
        }
        // END XARAYA modification
        return $this->getUpdateCount();
    }

    /**
     * Start a database transaction.
     * @throws SQLException
     * @return void
     */
    protected function beginTrans()
    {
        $result = @mssql_query('BEGIN TRAN', $this->dblink);
        if (!$result) {
            throw new SQLException('Could not begin transaction', mssql_get_last_message());
        }
    }
    
    /**
     * Commit the current transaction.
     * @throws SQLException
     * @return void
     */
    protected function commitTrans()
    {
        if (!@mssql_select_db($this->database, $this->dblink)) {
            throw new SQLException('No database selected');
        }
        $result = @mssql_query('COMMIT TRAN', $this->dblink);
        if (!$result) {
            throw new SQLException('Could not commit transaction', mssql_get_last_message());
        }
    }

    /**
     * Roll back (undo) the current transaction.
     * @throws SQLException
     * @return void
     */
    protected function rollbackTrans()
    {
        if (!@mssql_select_db($this->database, $this->dblink)) {            
            throw new SQLException('no database selected');
        }
        $result = @mssql_query('ROLLBACK TRAN', $this->dblink);
        if (!$result) {
            throw new SQLException('Could not rollback transaction', mssql_get_last_message());
        }
    }

    /**
     * Gets the number of rows affected by the last query.
     * if the last query was a select, returns 0.
     *
     * @return int Number of rows affected by the last query
     * @throws SQLException
     */
    function getUpdateCount()
    {       
        $res = @mssql_query('select @@rowcount', $this->dblink);
        if (!$res) {
            throw new SQLException('Unable to get affected row count', mssql_get_last_message());
        }
        $ar = @mssql_fetch_row($res);
        if (!$ar) {
            $result = 0;
        } else {
            @mssql_free_result($res);
            $result = $ar[0];
        }
        
        return $result;
    }          
    
    
    /**
     * Creates a CallableStatement object for calling database stored procedures.
     * 
     * @param string $sql
     * @return CallableStatement
     * @throws SQLException
     */
    function prepareCall($sql) 
    {             
        require_once 'creole/drivers/mssql/MSSQLCallableStatement.php';
        $stmt = mssql_init($sql);
        if (!$stmt) {
            throw new SQLException('Unable to prepare statement', mssql_get_last_message(), $sql);
        }
        return new MSSQLCallableStatement($this, $stmt);
    }
}
