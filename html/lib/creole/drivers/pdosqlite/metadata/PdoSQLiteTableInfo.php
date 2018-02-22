<?php
/*
 *  $Id: SQLiteTableInfo.php,v 1.8 2005/10/18 02:27:50 hlellelid Exp $
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
 
require_once 'creole/drivers/sqlite/metadata/SQLiteTableInfo.php';

/**
 * PdoSQLite implementation of TableInfo.
 * 
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Randy Syring <randy@rcs-comp.com>
 * @version   $Revision: 1.8 $
 * @package   creole.drivers.sqlite.metadata
 */
class PdoSQLiteTableInfo extends SQLiteTableInfo {
    
    protected $statement;
    
    protected $i2statement;
    
    protected function prepTable() {
        $sql = 'PRAGMA table_info('.$this->name.')';
        
        try {
            $this->statement = $this->dblink->prepare($sql);
            $this->statement->execute();
        } catch( PDOException $e ) {
            throw new SQLException('Could not get table info', $e->getMessage(), $sql);
        }
    }

    protected function getRow() {
        return $this->statement->fetch(PDO::FETCH_ASSOC);
    }
    
    protected function prepIndex1() {
        $sql = "PRAGMA index_list('".$this->name."')";
                
        try {
            $this->statement = $this->dblink->prepare($sql);
            $this->statement->execute();
        } catch( PDOException $e ) {
            throw new SQLException('Could not get index info', $e->getMessage(), $sql);
        }
    }
    
    protected function prepIndex2($name) {
        $sql = "PRAGMA index_info('$name')";
        
        try {
            $this->i2statement = $this->dblink->prepare($sql);
            $this->i2statement->execute();
        } catch( PDOException $e ) {
            throw new SQLException("Could not get index info for: $name", $e->getMessage(), $sql);
        }
    }
    
    protected function getI2Row() {
        return $this->i2statement->fetch(PDO::FETCH_ASSOC);
    }
    
}

?>
