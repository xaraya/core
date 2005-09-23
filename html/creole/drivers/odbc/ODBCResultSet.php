<?php
/*
 *  $Id: ODBCResultSet.php,v 1.1 2004/07/27 23:08:30 hlellelid Exp $
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

require_once 'creole/drivers/odbc/ODBCResultSetCommon.php';

/**
 * ODBC implementation of ResultSet.
 *
 * If the current ODBC driver does not support LIMIT or OFFSET natively,
 * the methods in here perform some adjustments and extra checking to make
 * sure that this behaves the same as RDBMS drivers using native OFFSET/LIMIT.
 *
 * NOTE: If the driver you are using does not support the odbc_num_rows()
 *       function, then you should use the {@link ODBCCachedResultSet} class
 *       instead.
 *
 * @author    Dave Lawson <dlawson@masterytech.com>
 * @version   $Revision: 1.1 $
 * @package   creole.drivers.odbc
 */
class ODBCResultSet extends ODBCResultSetCommon implements ResultSet
{
    /**
     * Number of rows in resultset.
     *
     * @var int
     */
    protected $numRows = 0;

    /**
     * @see ResultSet::__construct()
     */
    public function __construct(Connection $conn, $result, $fetchmode = null)
    {
        parent::__construct($conn, $result, $fetchmode);

        /**
         * Some ODBC drivers appear not to handle odbc_num_rows() very well when
         * more than one result handle is active at once. For example, the MySQL
         * ODBC driver always returns the number of rows for the last executed
         * result. For this reason, we'll store the row count here.
         */
        $this->numRows = @odbc_num_rows($result->getHandle());

        if ($this->numRows == -1)
            throw new SQLException('Error getting record count', $conn->nativeError());
    }

    /**
     * @see ODBCResultSetCommon::close()
     */
    function close()
    {
        parent::close();
        $numRows = 0;
    }

    /**
     * @see ResultSet::seek()
     */
    public function seek($rownum)
    {
        if ($rownum < 0 || $rownum > $this->getRecordCount()+1)
            return false;

        $this->cursorPos = $rownum;

        return true;
    }

    /**
     * @see ResultSet::next()
     */
    public function next()
    {
        if ($this->limit > 0 && ($this->cursorPos >= $this->limit)) {
            $this->afterLast();
            return false;
        }

        $rowNum = $this->offset + $this->cursorPos + 1;

        $cols = @odbc_fetch_into($this->result->getHandle(), $this->fields, $rowNum);

        if ($cols === false) {
            $this->afterLast();
            return false;
        }

        $this->cursorPos++;

        if ($this->fetchmode == ResultSet::FETCHMODE_ASSOC)
        {
            for ($i = 0, $n = count($this->fields); $i < $n; $i++)
            {
                $colname = @odbc_field_name($this->result->getHandle(), $i+1);
                $a[$colname] = $this->fields[$i];
            }

            $this->fields = $a;

            if (!$this->ignoreAssocCase)
                $this->fields = array_change_key_case($this->fields, CASE_LOWER);
        }

        return true;
    }

    /**
     * @see ResultSet::getRecordCount()
     */
    function getRecordCount()
    {
        $numrows = $this->numRows;

        // adjust count based on emulated limit/offset
        $numrows -= $this->offset;

        return ($this->limit > 0 && $numrows > $this->limit ? $this->limit : $numrows);
    }

    /**
     * @see ResultSet::getBlob()
     */
    public function getBlob($column)
    {
        require_once 'creole/util/Blob.php';
        $idx = (is_int($column) ? $column - 1 : $column);
        if (!array_key_exists($idx, $this->fields)) { throw new SQLException("Invalid resultset column: " . $column); }
        $data = $this->readLobData($column, ODBC_BINMODE_RETURN, $this->fields[$idx]);
        if (!$data) { return null; }
        $b = new Blob();
        $b->setContents($data);
        return $b;
    }

    /**
     * @see ResultSet::getClob()
     */
    public function getClob($column)
    {
        require_once 'creole/util/Clob.php';
        $idx = (is_int($column) ? $column - 1 : $column);
        if (!array_key_exists($idx, $this->fields)) { throw new SQLException("Invalid resultset column: " . $column); }
        $data = $this->readLobData($column, ODBC_BINMODE_CONVERT, $this->fields[$idx]);
        if (!$data) { return null; }
        $c = new Clob();
        $c->setContents($data);
        return $c;
    }

}