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
class SQLiteConnection extends ConnectionCommon implements Connection
{
    /**
     * The case to use for SQLite results.
     * (0=nochange, 1=upper, 2=lower)
     * This is set in each call to executeQuery() in order to ensure that different
     * Connections do not overwrite each other's settings
     */
    private $sqliteAssocCase;

    /**
     * @see Connection::connect()
     */
    public function connect($dsninfo, $flags = 0)
    {
        if (!extension_loaded('sqlite3')) {
            throw new SQLException('sqlite extension not loaded');
        }
        // XARAYA MODIFICATION
        $file = $dsninfo['databaseName'];
        // END XARAYA MODIFICATION

        $this->dsn = $dsninfo;
        $this->flags = $flags;

        $persistent = ($flags & Creole::PERSISTENT === Creole::PERSISTENT);

        if (PHP_VERSION == '5.0.4' || PHP_VERSION == '5.0.5') {
            $nochange = true;
        } else {
            $nochange = !(($flags & Creole::COMPAT_ASSOC_LOWER) === Creole::COMPAT_ASSOC_LOWER);
        }

        if ($nochange) {
            $this->sqliteAssocCase = 0;
        } else {
            $this->sqliteAssocCase = 2;
        }

        if ($file === null) {
            throw new SQLException("No SQLite database specified.");
        }

        $mode = (isset($dsninfo['mode']) && is_numeric($dsninfo['mode'])) ? $dsninfo['mode'] : 0644;

        if ($file != ':memory:') {
            if (!file_exists($file)) {
                if(!@touch($file)) {
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

        // XARAYA MODIFICATION
        // Use the SQLite3 class
        try {
        	// TODO: add flags here
        	$conn = new SQLite3($file);
        	// Have SQLite throw exceptions, rather than errors
        	$conn->enableExceptions(true);
        } catch (Exception $e) {
            throw new SQLException("Unable to connect to SQLite database");
        }
        // END XARAYA MODIFICATION

        $this->dblink = $conn;
    }

    /**
     * @see Connection::getDatabaseInfo()
     */
    public function getDatabaseInfo()
    {
        require_once 'creole/drivers/sqlite/metadata/SQLiteDatabaseInfo.php';
        return new SQLiteDatabaseInfo($this);
    }

    /**
    * @see Connection::getIdGenerator()
    */
    public function getIdGenerator()
    {
        require_once 'creole/drivers/sqlite/SQLiteIdGenerator.php';
        return new SQLiteIdGenerator($this);
    }

    /**
     * @see Connection::prepareStatement()
     */
    public function prepareStatement($sql)
    {
        require_once 'creole/drivers/sqlite/SQLitePreparedStatement.php';
        return new SQLitePreparedStatement($this, $sql);
    }

    /**
     * @see Connection::prepareCall()
     */
    public function prepareCall($sql)
    {
        throw new SQLException('SQLite does not support stored procedures using CallableStatement.');
    }

    /**
     * @see Connection::createStatement()
     */
    public function createStatement()
    {
        require_once 'creole/drivers/sqlite/SQLiteStatement.php';
        return new SQLiteStatement($this);
    }

    /**
     * @see Connection::close()
     */
    public function close()
    {
        @sqlite_close($this->dblink);
        $this->dblink = null;
        return null;
    }

    /**
     * @see Connection::applyLimit()
     */
    public function applyLimit(&$sql, $offset, $limit)
    {
        if ($limit > 0) {
            $sql .= " LIMIT " . $limit . ($offset > 0 ? " OFFSET " . $offset : "");
        } elseif ($offset > 0) {
            $sql .= " LIMIT -1 OFFSET " . $offset;
        }
    }

    /**
     * @see Connection::executeQuery()
     */
    public function executeQuery($sql, $fetchmode = null)
    {
        ini_set('sqlite.assoc_case', $this->sqliteAssocCase);
        $this->lastQuery = $sql;
        // XARAYA MODIFICATION
        try {
	        $result = $this->dblink->query($this->lastQuery);
        } catch (Exception $e) {
        	throw $e;
        }
        if (!$result) {
            $nativeError = $this->dblink->lastErrorMsg();
            throw new SQLException('Could not execute query', $nativeError, $this->lastQuery);
        }
        // END XARAYA MODIFICATION
        require_once 'creole/drivers/sqlite/SQLiteResultSet.php';
        return new SQLiteResultSet($this, $result, $fetchmode);
    }

    /**
     * @see Connection::executeUpdate()
     */
    public function executeUpdate($sql)
    {
        $this->lastQuery = $sql;
        // XARAYA MODIFICATION
        $result = $this->dblink->query($this->lastQuery);
        if (!$result) {
            $nativeError = $this->dblink->lastErrorMsg();
            throw new SQLException('Could not execute update', $nativeError, $this->lastQuery);
        }
        return (int) $this->dblink->changes();
        // END XARAYA MODIFICATION
    }

    /**
     * Start a database transaction.
     * @throws SQLException
     * @return void
     */
    protected function beginTrans()
    {
        $result = $this->dblink->query('BEGIN');
        if (!$result) {
            $nativeError = $this->dblink->lastErrorMsg();
            throw new SQLException('Could not begin transaction', $nativeError);
        }
    }

    /**
     * Commit the current transaction.
     * @throws SQLException
     * @return void
     */
    protected function commitTrans()
    {
        $result = $this->dblink->query('COMMIT');
        if (!$result) {
            $nativeError = $this->dblink->lastErrorMsg();
            throw new SQLException('Can not commit transaction', $nativeError);
        }
    }

    /**
     * Roll back (undo) the current transaction.
     * @throws SQLException
     * @return void
     */
    protected function rollbackTrans()
    {
        $result = $this->dblink->query('ROLLBACK');
        if (!$result) {
            $nativeError = $this->dblink->lastErrorMsg();
            throw new SQLException('Could not rollback transaction', $nativeError);
        }
    }

    /**
     * Gets the number of rows affected by the data manipulation
     * query.
     *
     * @return int Number of rows affected by the last query.
     */
    public function getUpdateCount()
    {
        // XARAYA MODIFICATION
        return (int) $this->dblink->changes();
        // END XARAYA MODIFICATION
    }

}
