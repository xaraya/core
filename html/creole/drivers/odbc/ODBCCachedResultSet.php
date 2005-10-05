<?php
/*
 *  $Id: ODBCCachedResultSet.php,v 1.1 2004/07/27 23:08:30 hlellelid Exp $
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
require_once 'creole/drivers/odbc/ODBCTypes.php';

/**
 * ODBC implementation of a cached ResultSet.
 *
 * In addition to limit/offset emulation, this class implements a resultset
 * cache. This can be useful as a workaround for some ODBC drivers which lack
 * support for record count retrieval (odbc_num_rows()), reverse/absolute
 * cursor scrolling, etc. This class will cache rows _on-demand_. So if you
 * only read the first couple rows of a result, then only those rows will be
 * cached. However, note that if you call getRecordCount() or last(), the
 * class must read and cache all available records.
 *
 * To use this class, create a derived {@link ODBCAdapter} class which returns
 * an instance of ODBCCachedResultSet from the {@link ODBCAdapter::createResultSet()} method.
 * Specify the adapter via the query portion of the Connection URL:
 *
 * odbc://localhost/Driver=MySQL ODBC 3.51 Driver;Database=test?adapter=MySQL
 *
 * @author    Dave Lawson <dlawson@masterytech.com>
 * @version   $Revision: 1.1 $
 * @package   creole.drivers.odbc
 */
class ODBCCachedResultSet extends ODBCResultSetCommon implements ResultSet 
{
    /**
     * Record cache
     * @var array
     */
    protected $recs = array();

    /**
     * Tracks the last cursor position of the recordset.
     */
    protected $lastPos = -1;

    /**
     * True if blobs/clobs should also be cached.
     */
    protected $cacheLobs = false;

    /**
     * @see ResultSet::__construct()
     */
    public function __construct(Connection $conn, $result, $fetchmode = null, $cacheLobs = false)
    {
        parent::__construct($conn, $result, $fetchmode);

        $this->cacheLobs = $cacheLobs;
    }

    /**
     * @see ODBCResultSetCommon::close()
     */
    function close()
    {
        parent::close();
        $this->recs = null;
        $this->lastPos = -1;
        $this->cacheLobs = false;
    }

    /**
     * Caches specified records up to and including the specified 1-based
     * record position. If -1 is specified, all records will be cached.
     * @throws SQLException
     */
    public function loadCache($recPos = -1)
    {
        $curRecs = count($this->recs);
        $totRecs = ($curRecs ? $this->offset + $curRecs : 0);

        while (1)
        {
            // Is record already cached?
            if ($this->lastPos != -1 || ($recPos > -1 && $recPos <= $curRecs))
                return;

            $row = array();
            $rowNum = ++$totRecs;

            $cols = @odbc_fetch_into($this->result->getHandle(), $row, $rowNum);

            // All records cached?
            if ($cols === false || ($this->limit > 0 && $curRecs+1 > $this->limit))
            {
                $this->lastPos = $curRecs;
                continue;
            }

            // Ignore offset records.
            if ($totRecs <= $this->offset)
                continue;

            // Cache lobs if necessary
            if ($this->cacheLobs)
            {
                $numFlds = @odbc_num_fields($this->result->getHandle());

                for ($fldNum = 1; $fldNum <= $numFlds; $fldNum++)
                {
                    ODBCTypes::loadTypeMap($this->conn);

                    $nativeType = @odbc_field_type($this->result->getHandle(), $fldNum);
                    $creoleType = ODBCTypes::getType($nativeType);

                    $isBlob = ($creoleType == CreoleTypes::BLOB ||
                               $creoleType == CreoleTypes::LONGVARBINARY);

                    $isClob = ($creoleType == CreoleTypes::CLOB ||
                               $creoleType == CreoleTypes::LONGVARCHAR);

                    if (($isBlob || $isClob) && $row[$fldNum-1] !== null)
                    {
                        $binmode = ($isBlob ? ODBC_BINMODE_RETURN : ODBC_BINMODE_CONVERT);
                        $curdata = $row[$fldNum-1];
                        $row[$fldNum-1] = $this->readLobData($fldNum, $binmode, $curdata);
                    }
                }
            }

            // Add record to cache.
            $this->recs[++$curRecs] = $row;
        }
    }

    /**
     * @see ResultSet::seek()
     */
    public function seek($rownum)
    {
        $this->loadCache($rownum);

        if ($rownum < 0 || $rownum > count($this->recs)+1)
            return false;

        $this->cursorPos = $rownum;

        return true;
    }

    /**
     * @see ResultSet::next()
     */
    function next()
    {
        $this->loadCache(++$this->cursorPos);

        if ($this->isAfterLast())
        {
            $this->afterLast();
            return false;
        }

        $this->fields = $this->recs[$this->cursorPos];

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
        if ($this->lastPos == -1)
            $this->loadCache(-1);

        return $this->lastPos;
    }

    /**
     * @see ResultSet::isAfterLast()
     */
    public function isAfterLast()
    {
        // All records cached yet?
        if ($this->lastPos == -1)
            return false;

        return ($this->cursorPos > $this->lastPos);
    }

}
