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

require_once 'creole/ResultSet.php';
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
class SQLiteResultSet extends ResultSetCommon implements ResultSet
{
    /**
     * Gets optimized SQLiteResultSetIterator.
     * @return SQLiteResultSetIterator
     */
    public function getIterator(): Traversable
    {
        require_once 'creole/drivers/sqlite/SQLiteResultSetIterator.php';
        return new SQLiteResultSetIterator($this);
    }

    /**
     * @see ResultSet::seek()
     */
    public function seek($rownum)
    {
        // MySQL rows start w/ 0, but this works, because we are
        // looking to move the position _before_ the next desired position
        if (!@sqlite_seek($this->result, $rownum)) {
            return false;
        }
        $this->cursorPos = $rownum;
        return true;
    }

    /**
     * @see ResultSet::next()
     */
    public function next()
    {
        // XARAYA MODIFICATION
        $this->fields = $this->result->fetchArray($this->fetchmode); // (ResultSet::FETCHMODE_NUM = SQLITE_NUM, etc.)
        // END XARAYA MODIFICATION
        if (!$this->fields) {
        	// XARAYA MODIFICATION
            $errno = $this->conn->getResource()->lastErrorCode();
        	// END XARAYA MODIFICATION
        	
        	// XARAYA MODIFICATION
        	// Check for both SQLITE_OK and SQLITE_DONE
            if (($errno == 0) || ($errno == 101)) {
        	// END XARAYA MODIFICATION

                // We've advanced beyond end of recordset.
                $this->afterLast();
                return false;
            } else {
        		// XARAYA MODIFICATION
                throw new SQLException("Error fetching result", $this->conn->getResource()->lastErrorMsg());
        		// END XARAYA MODIFICATION
            }
        }

        // strip out the table part of the field names so we get
        // just the column part
        $keys = array_keys($this->fields);
        foreach($keys as $key) {
            if(($pos = strpos($key, '.')) !== false) {
                $newkey = substr($key, $pos + 1);
                $this->fields[$newkey] = $this->fields[$key];
                unset($this->fields[$key]);
            }
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
        // XARAYA MODIFICATION
		$this->result->reset();
		$result = $this->result->fetchArray();
		$rows = is_array($result) ? $result['count'] : 0;
        // END XARAYA MODIFICATION

        if ($rows === null) {
        	// XARAYA MODIFICATION
            throw new SQLException("Error fetching num rows", $this->conn->getResource()->lastErrorMsg());
        	// END XARAYA MODIFICATION
		}
        return (int) $rows;
    }

    /**
     * Performs sqlite_udf_decode_binary on binary data.
     * @see ResultSet::getBlob()
     */
    public function getBlob($column)
    {
        $idx = (is_int($column) ? $column - 1 : $column);
        if (!array_key_exists($idx, $this->fields)) {
            throw new SQLException("Invalid resultset column: " . $column);
        }
        if ($this->fields[$idx] === null) {
            return null;
        }
        require_once 'creole/util/Blob.php';
        $b = new Blob();
        $b->setContents(sqlite_udf_decode_binary($this->fields[$idx]));
        return $b;
    }

    /**
     * Simply empties array as there is no result free method for sqlite.
     * @see ResultSet::close()
     */
    public function close()
    {
        $this->fields = array();
        $this->result = null;
    }
}
