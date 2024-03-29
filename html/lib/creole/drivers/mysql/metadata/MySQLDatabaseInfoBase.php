<?php
/*
 * $Id: MySQLDatabaseInfo.php,v 1.13 2006/01/17 19:44:39 hlellelid Exp $
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

require_once 'creole/metadata/DatabaseInfo.php';

/**
 * MySQL implementation of DatabaseInfo (base class)
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.13 $
 * @package   creole.drivers.mysql.metadata
 */
abstract class MySQLDatabaseInfoBase extends DatabaseInfo
{
    protected $driverName;

    protected $tableClassName;

    /**
     * @throws SQLException
     * @return void
     */
    protected function initTables()
    {
        include_once "creole/drivers/{$this->driverName}/metadata/{$this->tableClassName}.php";

        $sql = "SHOW TABLES FROM `" . $this->dbname . "`";

        $result = $this->sqlQuery($sql);

        if (!$result) {
            throw new SQLException("Could not list tables", mysql_error($this->conn->getResource()));
        }

        while ($row = $this->fetchRow($result)) {
            $this->tables[strtoupper($row[0])] = new $this->tableClassName($this, $row[0]);
        }

        $this->tablesLoaded = true;
    }

    /**
     * MySQL does not support sequences.
     *
     * @return void
     * @throws SQLException
     */
    protected function initSequences()
    {
        // throw new SQLException("MySQL does not support sequences natively.");
    }

    abstract public function sqlQuery($sql);

    abstract public function fetchRow($result);

    abstract public function fetchAssoc($result);

    abstract public function selectDb($dbName);
}
