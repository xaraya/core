<?php
/*
 * $Id: MySQLiResultSet.php,v 1.5 2006/01/17 19:44:39 hlellelid Exp $
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
 * MySQLi implementation of ResultSet.
 *
 * MySQL supports OFFSET / LIMIT natively; this means that no adjustments or checking
 * are performed.  We will assume that if the lmitSQL() operation failed that an
 * exception was thrown, and that OFFSET/LIMIT will never be emulated for MySQL.
 *
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @version   $Revision: 1.5 $
 * @package   creole.drivers.mysqli
 */
class MySQLiResultSet extends ResultSetCommon implements ResultSet
{
    /**
     * @see ResultSet::seek()
     */
    public function seek($rownum)
    {
		// XARAYA MODIFICATION
		if (($rownum < 0) || ($rownum > $this->getRecordCount())) {
			return false;
		}
        // MySQL rows start w/ 0, but this works, because we are
        // looking to move the position _before_ the next desired position
        if (!@mysqli_data_seek($this->result, $rownum)) {
            return false;
        }

        $this->cursorPos = $rownum;
		// END XARAYA MODIFICATION

        return true;
    }
    
    public function first()
    {
		// XARAYA MODIFICATION
        $this->seek(0);
    	$result = mysqli_fetch_array($this->result, $this->fetchmode);
        $this->seek(0);
		if ($result === false) {
			// No result, return false
			return $result;
		} elseif (is_array($result)) {
			// Good result put the fetched fields where they need to be, adjust the cursor posiition and return true.
			$this->fields = $result;
			$this->cursorPos = 0;
			return true;
		} elseif (null === $result) {
			// Indicates a successful call, but no rows fetched; return false for now
			return false;
		} else {
			// Not supposed to happen
			echo xarML('seek() returned an unknown result');
			exit;
		}
		// END XARAYA MODIFICATION
    }

    /**
     * @see ResultSet::next()
     */
    public function next()
    {
    	$this->fields = mysqli_fetch_array($this->result, $this->fetchmode);

        if (!$this->fields) {
			// XARAYA MODIFICATION
	        $resource = $this->conn->getResource();
			// END XARAYA MODIFICATION
            $errno = mysqli_errno($resource);

            if (!$errno) {
                // We've advanced beyond end of recordset.
                $this->afterLast();
                return false;
            } else {
                throw new SQLException("Error fetching result", mysqli_error($resource));
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
    public function getRecordCount()
    {
        $rows = @mysqli_num_rows($this->result);

        if ($rows === null) {
            throw new SQLException("Error fetching num rows", mysqli_error($this->conn->getResource()));
        }

        return (int) $rows;
    }

    /**
     * @see ResultSet::close()
     */
    public function close()
    {
        // Throws an error if we do not include the is_resource condition
        // Apparently at this point it is not a resource, at least in some cases
        if (isset($this->result) && is_resource($this->result)) {
            mysqli_free_result($this->result);
        } else {
            // Remove it anyway
            unset($this->result);
        }
        $this->fields = array();
    }

    /**
     * Get string version of column.
     * No rtrim() necessary for MySQL, as this happens natively.
     * @see ResultSet::getString()
     */
	// XARAYA MODIFICATION
    public function getString($column=null)
	// END XARAYA MODIFICATION
    {
        $idx = (is_int($column) ? $column - 1 : $column);

        if (!array_key_exists($idx, $this->fields)) {
            throw new SQLException("Invalid resultset column: " . $column);
        }

        if ($this->fields[$idx] === null) {
            return null;
        }

        return (string) $this->fields[$idx];
    }

    /**
     * Returns a unix epoch timestamp based on either a TIMESTAMP or DATETIME field.
     * @param mixed $column Column name (string) or index (int) starting with 1.
     * @return string
     * @throws SQLException - If the column specified is not a valid key in current field array.
     */
    public function getTimestamp($column, $format = 'Y-m-d H:i:s')
    {
        if (is_int($column)) {
            // because Java convention is to start at 1
            $column--;
        }

        if (!array_key_exists($column, $this->fields)) {
            throw new SQLException("Invalid resultset column: " . (is_int($column) ? $column + 1 : $column));
        }

        if ($this->fields[$column] === null) {
            return null;
        }

        $ts = strtotime($this->fields[$column]);

        if ($ts === -1 || $ts === false) { // in PHP 5.1 return value changes to FALSE
            // otherwise it's an ugly MySQL timestamp!
            // YYYYMMDDHHMMSS
            if (preg_match('/([\d]{4})([\d]{2})([\d]{2})([\d]{2})([\d]{2})([\d]{2})/', $this->fields[$column], $matches)) {
                //              YYYY       MM       DD       HH       MM       SS
                //                $1       $2       $3       $4       $5       $6
                $ts = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            }
        }

        if ($ts === -1 || $ts === false) { // in PHP 5.1 return value changes to FALSE
            // if it's still -1, then there's nothing to be done; use a different method.
            throw new SQLException("Unable to convert value at column " . (is_int($column) ? $column + 1 : $column) . " to timestamp: " . $this->fields[$column]);
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
}
