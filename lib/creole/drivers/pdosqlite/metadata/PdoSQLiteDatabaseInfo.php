<?php
/*
 *  $Id: SQLiteDatabaseInfo.php,v 1.3 2004/03/20 04:16:50 hlellelid Exp $
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
 * PdoSQLite implementation of DatabaseInfo.
 * 
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.3 $
 * @package   creole.drivers.sqlite.metadata
 */ 
class PdoSQLiteDatabaseInfo extends DatabaseInfo {
    
    /**
     * @throws SQLException
     * @return void
     */
    protected function initTables()
    {
        include_once 'creole/drivers/pdosqlite/metadata/PdoSQLiteTableInfo.php';        
        
        $sql = "SELECT name FROM sqlite_master WHERE type='table' UNION ALL SELECT name FROM sqlite_temp_master WHERE type='table' ORDER BY name;";
        
        try {
            $statement = $this->dblink->prepare($sql);
            $statement->execute();
        } catch( PDOException $e ) {
            throw new SQLException('Could not list tables', $e->getMessage(), $sql);
        }
        
        while ($row = $statement->fetch()) {
            $this->tables[strtoupper($row[0])] = new PdoSQLiteTableInfo($this, $row[0]);
        }
    }
    
    /**
     * SQLite does not support sequences.
     *
     * @return void 
     * @throws SQLException
     */
    protected function initSequences()
    {
        // throw new SQLException("MySQL does not support sequences natively.");
    }
        
}
