<?php
/*
 *  $Id: MSSQLCallableStatement.php,v 1.20 2005/09/16 13:09:50 hlellelid Exp $
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

require_once 'creole/drivers/mssql/MSSQLPreparedStatement.php';
require_once 'creole/CallableStatement.php';
include_once 'creole/CreoleTypes.php';

/**
 * MS SQL Server class to handle stored procedure execution.
 *
 * Developer note:
 *
 *    There is no CallableStatement superclass.  Unlike JDBC, Creole
 *    uses abstract parent classes rather than interfaces -- in order
 *    to minimize code duplication.  Since PHP doesn't support multiple
 *    inheritance, the DRIVERCallableStatement class cannot extend both
 *    the DRIVERPreparedStatement class and the would-be abstract
 *    CallableStatement class.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 * @version $Revision: 1.20 $
 * @package creole.drivers.mssql
 */
class MSSQLCallableStatement extends MSSQLPreparedStatement implements CallableStatement
{
    /** Output variables */
    private $boundOutVars = array();

    /**
     * Match Creole types to SQL Server types
     * @var array
     */
    private static $typeMap = array(
        CreoleTypes::BOOLEAN => SQLBIT,
        CreoleTypes::BIGINT => SQLINT4,
        CreoleTypes::SMALLINT => SQLINT2,
        CreoleTypes::TINYINT => SQLINT2,
        CreoleTypes::INTEGER => SQLINT4,
        CreoleTypes::CHAR => SQLCHAR,
        CreoleTypes::VARCHAR => SQLVARCHAR,
        CreoleTypes::TEXT => SQLTEXT,
        CreoleTypes::FLOAT => SQLFLT8,
        CreoleTypes::DOUBLE => SQLFLT8,
        CreoleTypes::DATE => SQLVARCHAR,
        CreoleTypes::TIME => SQLVARCHAR,
        CreoleTypes::TIMESTAMP => SQLVARCHAR,
        CreoleTypes::VARBINARY => SQLVARCHAR,
        CreoleTypes::NUMERIC => SQLINT4,
        CreoleTypes::DECIMAL => SQLFLT8
    );

    /**
     * Statement created by mssql_init()
     * @var resource
     */
    private $stmt;


    /**
     * The result resource.
     * @var resource
     */
    private $result;

    /**
     * Construct new MSSQLCallableStatement.
     *
     * @param Connection $conn
     * @param resource $stmt
     */
    public function __construct(Connection $conn, $stmt)
    {
        $this->conn = $conn;
        $this->stmt = $stmt;
    }

    /**
     * @see CallableStatement::getResource()
     */
    public function getResource()
    {
        return $this->stmt;
    }

    /**
     * @see CallableStatement::close()
     */
    public function close()
    {
        @mssql_free_statement($this->stmt);
        $this->rsFetchCount = 0;
    }

    /**
     * @see CallableStatement::executeQuery()
     */
    public function executeQuery($p1 = null, $fetchmode = null)
    {
        $params = null;
        if ($fetchmode !== null) {
            $params = $p1;
        } elseif ($p1 !== null) {
            if (is_array($p1)) {
                $params = $p1;
            } else {
                $fetchmode = $p1;
            }
        }

        if ($params) {
            for($i = 0,$cnt = count($params); $i < $cnt; $i++) {
                $this->set($i + 1, $params[$i]);
            }
        }

        $this->result = mssql_execute($this->stmt);
        if (!$this->result) {
            throw new SQLException('unable to execute callable statement', mssql_get_last_message());
        }

        return new MSSQLResultSet($this->conn, $this->result, $fetchmode, $this->offset, $this->limit);
    }

    /**
     * @see CallableStatement::getMoreResults()
     */
    public function getMoreResults()
    {
        $this->rsFetchCount++; // we track this because
        $hasMore = mssql_next_result($this->result);
        if ($this->resultSet) {
            $this->resultSet->close();
        }
        if ($hasMore) {
            $clazz = $this->resultClass;
            $this->resultSet = new $clazz($this, $this->result);
        } else {
            $this->resultSet = null;
        }
        return $hasMore;
    }

    /**
     * @see CallableStatement::registerOutParameter()
     */
    public function registerOutParameter($paramIndex, $sqlType, $maxLength = null)
    {
        mssql_bind($this->stmt, $paramIndex, $this->boundOutVars[$paramIndex], self::$typeMap[$sqlType], true, false, $maxLength);
    }

    /**
     * @see CallableStatement::setArray()
     */
    public function setArray($paramIndex, $value, $out = false)
    {
        if ($out) {
            $this->boundOutVars[$paramIndex] = &$value;
        } // reference means that changes to value, will be reflected
        if ($value === null) {
            $this->setNull($paramIndex);
        } else {
            $value = serialize($value);
            mssql_bind($this->stmt, $paramIndex, $value, SQLTEXT, $out);
        }
    }

    /**
     * @see CallableStatement::setBoolean()
     */
    public function setBoolean($paramIndex, $value, $out = false)
    {
        if ($out) {
            $this->boundOutVars[$paramIndex] = &$value;
        } // reference means that changes to value, will be reflected
        if ($value === null) {
            $this->setNull($paramIndex);
        } else {
            $value = ($value) ? 1 : 0;
            mssql_bind($this->stmt, $paramIndex, $value, SQLBIT, $out);
        }
    }


    /**
     * @see CallableStatement::setBlob()
     */
    public function setBlob($paramIndex, $blob, $out = false)
    {
        if ($blob === null) {
            $this->setNull($paramIndex);
        } else {
            if (is_object($blob)) {
                $blob = $blob->__toString();
            }
            if ($out) {
                $this->boundOutVars[$paramIndex] = &$blob;
            } // reference means that changes to value, will be reflected
            $data = unpack("H*hex", $blob);
            mssql_bind($this->stmt, $paramIndex, $data, SQLTEXT, $out);
        }
    }

    /**
     * @see CallableStatement::setClob()
     */
    public function setClob($paramIndex, $clob, $out = false)
    {
        if ($clob === null) {
            $this->setNull($paramIndex);
        } else {
            if (is_object($clob)) {
                $clob = $clob->__toString();
            }
            if ($out) {
                $this->boundOutVars[$paramIndex] = &$clob;
            } // reference means that changes to value, will be reflected
            mssql_bind($this->stmt, $paramIndex, $clob, SQLTEXT, $out);
        }
    }

    /**
     * @see CallableStatement::setDate()
     */
    public function setDate($paramIndex, $value, $out = false)
    {
        if ($out) {
            $this->boundOutVars[$paramIndex] = &$value;
        } // reference means that changes to value, will be reflected
        if ($value === null) {
            $this->setNull($paramIndex);
        } else {
            if (is_numeric($value)) {
                $value = date("Y-m-d", $value);
            }
            mssql_bind($this->stmt, $paramIndex, $value, SQLVARCHAR, $out);
        }
    }

    /**
     * @see CallableStatement::setFloat()
     */
    public function setFloat($paramIndex, $value, $out = false)
    {
        if ($out) {
            $this->boundOutVars[$paramIndex] = &$value;
        } // reference means that changes to value, will be reflected
        if ($value === null) {
            $this->setNull($paramIndex);
        } else {
            $value = (float) $value;
            mssql_bind($this->stmt, $paramIndex, $value, SQLFLT8, $out);
        }
    }

    /**
     * @see CallableStatement::setInt()
     */
    public function setInt($paramIndex, $value, $out = false)
    {
        if ($out) {
            $this->boundOutVars[$paramIndex] = &$value;
        } // reference means that changes to value, will be reflected
        if ($value === null) {
            $this->setNull($paramIndex);
        } else {
            $value = (int) $value;
            mssql_bind($this->stmt, $paramIndex, $value, SQLINT4, $out);
        }
    }

    /**
     * @see CallableStatement::setNull()
     */
    public function setNull($paramIndex)
    {
        // hopefully type isn't essential here :)
        $value = null; // wants a var to pass by reference
        mssql_bind($this->stmt, $paramIndex, $value, $type = null, $out = false, $is_null = true);
    }

    /**
     * @see CallableStatement::setString()
     */
    public function setString($paramIndex, $value, $out = false)
    {
        if ($out) {
            $this->boundOutVars[$paramIndex] = &$value;
        } // reference means that changes to value, will be reflected
        if ($value === null) {
            $this->setNull($paramIndex);
        } else {
            $value = (string) $value;
            mssql_bind($this->stmt, $paramIndex, $value, SQLVARCHAR, $out);
        }
    }

    /**
     * @see CallableStatement::setTime()
     */
    public function setTime($paramIndex, $value, $out = false)
    {
        if ($out) {
            $this->boundOutVars[$paramIndex] = &$value;
        } // reference means that changes to value, will be reflected
        if ($value === null) {
            $this->setNull($paramIndex);
        } else {
            if (is_numeric($value)) {
                $value = date("H:i:s", $value);
            }
            mssql_bind($this->stmt, $paramIndex, $value, SQLVARCHAR, $out);
        }
    }

    /**
     * @see CallableStatement::setTimestamp()
     */
    public function setTimestamp($paramIndex, $value, $out = false)
    {
        if ($out) {
            $this->boundOutVars[$paramIndex] = &$value;
        } // reference means that changes to value, will be reflected
        if ($value === null) {
            $this->setNull($paramIndex);
        } else {
            if (is_numeric($value)) {
                $value = date('Y-m-d H:i:s', $value);
            }
            mssql_bind($this->stmt, $paramIndex, $value, SQLVARCHAR, $out);
        }
    }

    /**
     * @see CallableStatement::getArray()
     */
    public function getArray($paramIndex)
    {
        if (!array_key_exists($paramIndex, $this->boundOutVars)) {
            throw new SQLException('Requesting variable not bound to output var: '.$paramIndex);
        }
        if ($this->boundOutVars[$paramIndex] === null) {
            return null;
        }
        return (array) unserialize($this->boundOutVars[$paramIndex]);
    }

    /**
     * @see CallableStatement::getBoolean()
     */
    public function getBoolean($paramIndex)
    {
        if (!array_key_exists($paramIndex, $this->boundOutVars)) {
            throw new SQLException('Requesting variable not bound to output var: '.$paramIndex);
        }
        if ($this->boundOutVars[$paramIndex] === null) {
            return null;
        }
        return (bool) $this->boundOutVars[$paramIndex];
    }

    /**
     * @see CallableStatement::getBlob()
     */
    public function getBlob($paramIndex)
    {
        if (!array_key_exists($paramIndex, $this->boundOutVars)) {
            throw new SQLException('Requesting variable not bound to output var: '.$paramIndex);
        }
        if ($this->boundOutVars[$paramIndex] === null) {
            return null;
        }
        require_once 'creole/util/Blob.php';
        $b = new Blob();
        $b->setContents($this->boundOutVars[$paramIndex]);
        return $b;
    }

    /**
     * @see CallableStatement::getClob()
     */
    public function getClob($paramIndex)
    {
        if (!array_key_exists($paramIndex, $this->boundOutVars)) {
            throw new SQLException('Requesting variable not bound to output var: '.$paramIndex);
        }
        if ($this->boundOutVars[$paramIndex] === null) {
            return null;
        }
        require_once 'creole/util/Clob.php';
        $c = new Clob();
        $c->setContents($this->boundOutVars[$paramIndex]);
        return $c;
    }

    /**
     * @see CallableStatement::getDate()
     */
    public function getDate($paramIndex, $format = '%Y-%m-%d')
    {
        if (!array_key_exists($paramIndex, $this->boundOutVars)) {
            throw new SQLException('Requesting variable not bound to output var: '.$paramIndex);
        }
        if ($this->boundOutVars[$paramIndex] === null) {
            return null;
        }

        $ts = strtotime($this->boundOutVars[$paramIndex]);
        if ($ts === -1 || $ts === false) { // in PHP 5.1 return value changes to FALSE
            throw new SQLException("Unable to convert value at column " . $paramIndex . " to timestamp: " . $this->boundOutVars[$paramIndex]);
        }
        if (strpos($format, '%') !== false) {
            return strftime($format, $ts);
        } else {
            return date($format, $ts);
        }

        return $this->boundOutVars[$paramIndex];
    }

    /**
     * @param mixed $paramIndex Column name (string) or index (int).
     * @return float
     */
    public function getFloat($paramIndex)
    {
        if (!array_key_exists($paramIndex, $this->boundOutVars)) {
            throw new SQLException('Requesting variable not bound to output var: '.$paramIndex);
        }
        if ($this->boundOutVars[$paramIndex] === null) {
            return null;
        }
        return (float) $this->boundOutVars[$paramIndex];
    }

    /**
     * @see CallableStatement::getInt()
     */
    public function getInt($paramIndex)
    {
        if (!array_key_exists($paramIndex, $this->boundOutVars)) {
            throw new SQLException('Requesting variable not bound to output var: '.$paramIndex);
        }
        if ($this->boundOutVars[$paramIndex] === null) {
            return null;
        }
        return (int) $this->boundOutVars[$paramIndex];
    }

    /**
     * @see CallableStatement::getString()
     */
    public function getString($paramIndex)
    {
        if (!array_key_exists($paramIndex, $this->boundOutVars)) {
            throw new SQLException('Requesting variable not bound to output var: '.$paramIndex);
        }
        if ($this->boundOutVars[$paramIndex] === null) {
            return null;
        }
        return (string) $this->boundOutVars[$paramIndex];
    }

    /**
     * @see CallableStatement::getTime()
     */
    public function getTime($paramIndex, $format = '%X')
    {
        if (!array_key_exists($paramIndex, $this->boundOutVars)) {
            throw new SQLException('Requesting variable not bound to output var: '.$paramIndex);
        }
        if ($this->boundOutVars[$paramIndex] === null) {
            return null;
        }

        $ts = strtotime($this->boundOutVars[$paramIndex]);
        if ($ts === -1  || $ts === false) { // in PHP 5.1 return value changes to FALSE
            throw new SQLException("Unable to convert value at column " . $paramIndex . " to timestamp: " . $this->boundOutVars[$paramIndex]);
        }
        if (strpos($format, '%') !== false) {
            return strftime($format, $ts);
        } else {
            return date($format, $ts);
        }

    }

    /**
     * @see CallableStatement::getTimestamp()
     */
    public function getTimestamp($paramIndex, $format = 'Y-m-d H:i:s')
    {
        if (!array_key_exists($paramIndex, $this->boundOutVars)) {
            throw new SQLException('Requesting variable not bound to output var: '.$paramIndex);
        }
        if ($this->boundOutVars[$paramIndex] === null) {
            return null;
        }

        $ts = strtotime($this->boundOutVars[$paramIndex]);
        if ($ts === -1 || $ts === false) { // in PHP 5.1 return value changes to FALSE
            throw new SQLException("Unable to convert value at column " . $paramIndex . " to timestamp: " . $this->boundOutVars[$paramIndex]);
        }
        if (strpos($format, '%') !== false) {
            return strftime($format, $ts);
        } else {
            return date($format, $ts);
        }
    }

}
