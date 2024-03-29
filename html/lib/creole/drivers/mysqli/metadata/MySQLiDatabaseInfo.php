<?php
/*
 * $Id: MySQLiDatabaseInfo.php,v 1.3 2006/01/17 19:44:39 hlellelid Exp $
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

require_once 'creole/drivers/mysql/metadata/MySQLDatabaseInfoBase.php';

/**
 * MySQLi implementation of DatabaseInfo.
 *
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @version   $Revision: 1.3 $
 * @package   creole.drivers.mysqli.metadata
 */
class MySQLiDatabaseInfo extends MySQLDatabaseInfoBase
{
    protected $driverName = 'mysqli';

    protected $tableClassName = 'MySQLiTableInfo';

    public function sqlQuery($sql)
    {
        return @mysqli_query($this->conn->getResource(), $sql);
    }

    public function fetchRow($result)
    {
        return mysqli_fetch_row($result);
    }

    public function fetchAssoc($result)
    {
        return mysqli_fetch_assoc($result);
    }

    public function selectDb($dbName)
    {
        $this->tables = array();
        $this->tablesLoaded = false;
        $this->dbname = $dbName;
        return @mysqli_select_db($this->conn->getResource(), $dbName);
    }
}
