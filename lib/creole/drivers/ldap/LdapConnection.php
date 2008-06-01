<?php
/*
 *  $Id: LdapConnection.php,v 1.18 2004/09/01 14:00:28 dlawson_mi Exp $
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
include_once 'creole/drivers/ldap/LdapResultSet.php';

/**
 * Ldap implementation of Connection.
 *
 *
 * @author    Sébastien Cramatte <scramatte@zensoluciones.com>
 * @package   creole.drivers.ldap
 */
class LdapConnection extends ConnectionCommon implements Connection {

    /** Current DN . */
    private $basedn;
    private $bind;
    private $query;

    const PORT = 389;
    const TLSPORT = 636;

    /**
     * Connect to a database and log in as the specified user.
     *
     * @param $dsn the data source name (see DB::parseDSN for syntax)
     * @param $flags Any conneciton flags.
     * @access public
     * @throws SQLException
     * @return void
     */
    function connect($dsninfo, $flags = 0)
    {
        if (!extension_loaded('ldap')) {
            throw new SQLException('ldap extension not loaded');
        }

        $this->dsn = $dsninfo;
        $this->flags = $flags;

        $user = $dsninfo['username'];
        $pw = $dsninfo['password'];
        $dbport = $dsninfo['port'];

   			if ($dsninfo['phptype'] == 'ldaps') {
            $dbhost = 'ldaps://' . $dsninfo['hostspec'];
            if (!isset($dbport)) $dbport = LdapConnection::TLSPORT;

        } else {
            $dbhost = $dsninfo['hostspec'];
            $dbport = $dsninfo['port'];
            if (!isset($dbport)) $dbport = LdapConnection::PORT;
        }

        @ini_set('track_errors', true);
        if ($dbhost) {
            $conn = @ldap_connect($dbhost, $dbport);
            $bind = @ldap_bind($conn,$user, $pw);
        } else {
            $conn = false;
        }

        @ini_restore('track_errors');
        if (empty($conn)) {
            if (($err = @ldap_error()) != '') {
                throw new SQLException("connect failed", $err);
            } elseif (empty($php_errormsg)) {
                throw new SQLException("connect failed");
            } else {
                throw new SQLException("connect failed", $php_errormsg);
            }
        }

				if (!$dsninfo['database']) {
						throw new SQLException("You must specify base DN");
				}

				$this->basedn = $dsninfo['database'];

        $this->dblink = $conn;
        $this->bind = $bind;
    }

    /**
     * @see Connection::getDatabaseInfo()
     */
    public function getDatabaseInfo()
    {
        require_once 'creole/drivers/ldap/metadata/LdapDatabaseInfo.php';
        return new LdapDatabaseInfo($this);
    }

    /**
     * @see Connection::getIdGenerator()
     */
    public function getIdGenerator()
    {
        throw new SQLException('Ldap does not support id generation.');
    }

    /**
     * @see Connection::prepareStatement()
     */
    public function prepareStatement($sql)
    {
        require_once 'creole/drivers/ldap/LdapPreparedStatement.php';
        return new LdapPreparedStatement($this, $sql);
    }

    /**
     * @see Connection::prepareCall()
     */
    public function prepareCall($sql) {
        throw new SQLException('Ldap does not support stored procedures.');
    }

    /**
     * @see Connection::createStatement()
     */
    public function createStatement()
    {
        require_once 'creole/drivers/ldap/LdapStatement.php';
        return new LdapStatement($this);
    }

    /**
     * @see Connection::disconnect()
     */
    function close()
    {
        $ret = ldap_close($this->dblink);
        $this->dblink = null;
        return $ret;
    }

    /**
     * @see Connection::applyLimit()
     */
    public function applyLimit(&$sql, $offset, $limit)
    {
    	/*
        if ( $limit > 0 ) {
            $sql .= " LIMIT " . ($offset > 0 ? $offset . ", " : "") . $limit;
        } else if ( $offset > 0 ) {
            $sql .= " LIMIT " . $offset . ", 18446744073709551615";
        }
      */
    }

		private function createQuery($query) {
			if (!is_array($query))  $query = explode('?',$query);

		  /* return an array in the same order as specified in rfc 2255 */
			return array (	'dn'=> $query[0]?$query[0]:$this->basedn,
											'attributes' => $query[1]?explode(',',$query[1]):array('*'),
											'scope' => $query[2]?$query[2]:'base',   /* base, one, sub */
											'filter' => $query[3]?$query[3]:'(objectClass=*)',
											'extensions' => $query[4]
										);
		}

    /**
     * @see Connection::executeQuery()
     */
    function executeQuery($query, $fetchmode = null)
    {

    	$query = $this->createQuery($query);
    	$this->lastQuery = $query;

    	$result = @ldap_search($this->dblink,$query['dn'],$query['filter'],$query['attributes']);

    	if (!$result) {
            throw new SQLException('Could not execute search', ldap_error($this->dblink));
      }


      return new LdapResultSet($this, $result, $fetchmode);

    }

    /**
     * @see Connection::executeUpdate()
     */
    function executeUpdate($sql)
    {
    	/*
        $this->lastQuery = $sql;

        if ($this->database) {
            if (!@mysql_select_db($this->database, $this->dblink)) {
                    throw new SQLException('No database selected', mysql_error($this->dblink));
            }
        }

        $result = @mysql_query($sql, $this->dblink);
        if (!$result) {
            throw new SQLException('Could not execute update', mysql_error($this->dblink), $sql);
        }
        return (int) mysql_affected_rows($this->dblink);
      */
    }

    /**
     * Start a database transaction.
     * @throws SQLException
     * @return void
     */
    protected function beginTrans()
    {
				throw new SQLException('Ldap does not support stored procedures.');
    }

    /**
     * Commit the current transaction.
     * @throws SQLException
     * @return void
     */
    protected function commitTrans()
    {
        throw new SQLException('Ldap does not support stored procedures.');
    }

    /**
     * Roll back (undo) the current transaction.
     * @throws SQLException
     * @return void
     */
    protected function rollbackTrans()
    {
				throw new SQLException('Ldap does not support stored procedures.');
    }

    /**
     * Gets the number of rows affected by the data manipulation
     * query.
     *
     * @return int Number of rows affected by the last query.
     */
    function getUpdateCount()
    {
				throw new SQLException('Ldap does not support this feature.');
    }

}