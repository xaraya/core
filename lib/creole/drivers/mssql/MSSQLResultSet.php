<?php
/*
 *  $Id: MSSQLResultSet.php,v 1.21 2006/01/17 19:44:38 hlellelid Exp $
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

require_once 'creole/ResultSet.php';
require_once 'creole/common/ResultSetCommon.php';

/**
 * MSSQL implementation of ResultSet.
 *
 * MS SQL does not support LIMIT or OFFSET natively, but
 * the connection overrides applyLimit to handle this with SQL
 * manipulation.
 * 
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.21 $
 * @package   creole.drivers.mssql
 */
class MSSQLResultSet extends ResultSetCommon implements ResultSet {    
    /**
     * @see ResultSet::seek()
     */ 
    function seek($rownum)
    {
        // support emulated OFFSET
        $actual = $rownum; // + $this->offset;
        
        // MSSQL rows start w/ 0, but this works, because we are
        // looking to move the position _before_ the next desired position
         if (!@mssql_data_seek($this->result, $actual)) {
                return false;
        }

        $this->cursorPos = $rownum;
        return true;
    }
    
    /**
     * @see ResultSet::next()
     */
    function next()
    {
        if ($this->fetchmode === ResultSet::FETCHMODE_ASSOC) {
            $this->fields = mssql_fetch_assoc($this->result);
        } else {
            $this->fields = mssql_fetch_row($this->result);
        }
        
        if (!$this->fields) {
            if ($errmsg = mssql_get_last_message()) {
                throw new SQLException("Error fetching result", $errmsg);
             } else {
                // We've advanced beyond end of recordset.
                $this->afterLast();
                return false;
             }          
        }
        
        if ($this->fetchmode === ResultSet::FETCHMODE_ASSOC && $this->lowerAssocCase) {
            $this->fields = array_change_key_case($this->fields, CASE_LOWER);
        }
        
        // Advance cursor position
        $this->cursorPos++;
        return true;
    }
    
    /**
     * @see ResultSet::getRecordCount()
     */
    function getRecordCount()
    {
        $rows = @mssql_num_rows($this->result);
        if ($rows === null) {
            throw new SQLException('Error getting record count', mssql_get_last_message());
        }
        return $rows;
    }

    
    
    /**
     * @see ResultSet::getTime()
     */
    public function getTime($column, $format = '%X') 
    {
        $idx = (is_int($column) ? $column - 1 : $column);
        if (!array_key_exists($idx, $this->fields)) { throw new SQLException("Invalid resultset column: " . $column); }
        if ($this->fields[$idx] === null) { return null; }
        
        $fvalue = $this->fields[$idx];
        
        // msssql uses the below date to pad its date values since it doesn't
        // have a time-only field type
        $fvalue = str_replace('1900-01-01 ', '', $fvalue);
        
        $ts = strtotime($fvalue);
        
        if ($ts === -1 || $ts === false) { // in PHP 5.1 return value changes to FALSE
            throw new SQLException("Unable to convert value at column " . (is_int($column) ? $column + 1 : $column) . " to timestamp: " . $this->fields[$idx]);
        }
        if ($format === null) {
            return $ts;
        }        
        if (strpos($format, '%') !== false) {
            return strftime($format, $ts);
        } else {
            return date($format, $ts);
        }        
    }
    
    /**
     * @see ResultSet::close()
     */ 
    function close()
    {
        $ret = @mssql_free_result($this->result);
        $this->result = false;
        $this->fields = array();
        $this->limit = 0;
        $this->offset = 0;        
    }   

}
