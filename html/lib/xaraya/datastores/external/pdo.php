<?php
/**
 * External datastore for DD objects using PHP PDO connection from ExternalDatabase
 *
 * @package core\datastores
 * @subpackage datastores
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
**/

namespace Xaraya\DataObject\DataStores;

use PDODatabaseInfo;
use PDOStatement;
use sys;

sys::import('xaraya.datastores.external');

/**
 * External datastore for DD objects using PHP PDO connection from ExternalDatabase
 * ```
 * // @todo simplify config someday...
 * $config = [
 *     'dbConnIndex' => 1,
 *     'dbConnArgs' => [
 *         'external' => 'pdo',
 *         // ...
 *     ],
 * ];
 * $config['dbConnArgs'] = json_encode($config['dbConnArgs']);
 * ```
 */
class PdoDataStore extends ExternalDataStore
{
    /** @var \PDO|null */
    protected $db     = null;

    /**
     * Summary of doGetItem
     * @param mixed $itemid
     * @param string $tablename
     * @param array<string> $queryfields
     * @param string $wherefield
     * @return mixed
     */
    protected function doGetItem($itemid, $tablename, $queryfields, $wherefield)
    {
        $this->connect();
        $sql = "SELECT " . implode(", ", $queryfields) . " FROM $tablename WHERE $wherefield = ?";
        $stmt = $this->prepareStatement($sql);
        // Note: all values are treated as PDO::PARAM_STR here
        $params = [ $itemid ];
        $result = $stmt->execute($params);
        if (empty($result)) {
            return null;
        }
        return $stmt->fetch();
    }

    /**
     * Summary of doCreateItem
     * @param mixed $itemid
     * @param string $tablename
     * @param array<string, mixed> $values
     * @param string $wherefield
     * @return mixed
     */
    protected function doCreateItem($itemid, $tablename, $values, $wherefield = '')
    {
        if (!empty($itemid) && !empty($wherefield)) {
            $values[$wherefield] = $itemid;
        }
        $this->connect();
        $fields = [];
        $params = [];
        foreach ($values as $field => $value) {
            $fields[] = $field;
            $params[] = $value;
        }
        $sql = "INSERT INTO $tablename (" . implode(", ", $fields) . ") VALUES (?" . str_repeat(", ?", count($params) - 1) . ")";
        $stmt = $this->prepareStatement($sql);
        // Note: all values are treated as PDO::PARAM_STR here
        $result = $stmt->execute($params);
        if (empty($result)) {
            return null;
        }
        $itemid ??= $this->getLastId($tablename);
        return $itemid;
    }

    /**
     * Summary of doUpdateItem
     * @param mixed $itemid
     * @param string $tablename
     * @param array<string, mixed> $values
     * @param string $wherefield
     * @return mixed
     */
    protected function doUpdateItem($itemid, $tablename, $values, $wherefield)
    {
        $this->connect();
        $fields = [];
        $params = [];
        foreach ($values as $field => $value) {
            $fields[] = "$field = ?";
            $params[] = $value;
        }
        $params[] = $itemid;
        $sql = "UPDATE $tablename SET " . implode(", ", $fields) . " WHERE $wherefield = ?";
        $stmt = $this->prepareStatement($sql);
        // Note: all values are treated as PDO::PARAM_STR here
        $result = $stmt->execute($params);
        if (empty($result)) {
            return null;
        }
        return $itemid;
    }

    /**
     * Summary of doDeleteItem
     * @param mixed $itemid
     * @param string $tablename
     * @param string $wherefield
     * @return mixed
     */
    protected function doDeleteItem($itemid, $tablename, $wherefield)
    {
        $this->connect();
        $sql = "DELETE FROM $tablename WHERE $wherefield = ?";
        $stmt = $this->prepareStatement($sql);
        // Note: all values are treated as PDO::PARAM_STR here
        $params = [ $itemid ];
        $result = $stmt->execute($params);
        if (empty($result)) {
            return null;
        }
        return $itemid;
    }

    /**
     * Summary of doGetItems
     * @param array<int> $itemids
     * @param string $tablename
     * @param array<string> $queryfields
     * @param mixed $where
     * @param mixed $sort
     * @param int $startnum start number (default 1)
     * @param int $numitems number of items to retrieve (default 0 = all)
     * @return mixed
     */
    protected function doGetItems($itemids, $tablename, $queryfields, $where = null, $sort = null, $startnum = 1, $numitems = 0)
    {
        $this->connect();
        $sql = "SELECT " . implode(", ", $queryfields) . " FROM $tablename";
        // WHERE $wherefield = ?";
        $stmt = $this->prepareStatement($sql);
        // Note: all values are treated as PDO::PARAM_STR here
        //$params = [ $itemid ];
        //$result = $stmt->execute($params);
        $result = $stmt->execute();
        if (empty($result)) {
            return null;
        }
        return $stmt->fetchAll();
    }

    /**
     * Summary of doCountItems
     * @param array<int> $itemids
     * @param string $tablename
     * @param mixed $where
     * @return int
     */
    protected function doCountItems($itemids, $tablename, $where = null)
    {
        $this->connect();
        $sql = "SELECT COUNT(*) FROM $tablename";
        // WHERE $wherefield = ?";
        $stmt = $this->prepareStatement($sql);
        // Note: all values are treated as PDO::PARAM_STR here
        //$params = [ $itemid ];
        //$result = $stmt->execute($params);
        $result = $stmt->execute();
        if (empty($result)) {
            return 0;
        }
        return $stmt->fetchColumn();
    }

    /**
     * Summary of doGetAggregates
     * @param array<int> $itemids
     * @param string $tablename
     * @param array<string> $queryfields
     * @param mixed $where
     * @param mixed $sort
     * @param mixed $groupby
     * @param int $startnum start number (default 1)
     * @param int $numitems number of items to retrieve (default 0 = all)
     * @return mixed
     */
    protected function doGetAggregates($itemids, $tablename, $queryfields, $where = null, $sort = null, $groupby = null, $startnum = 1, $numitems = 0)
    {
        return null;
    }

    /**
     * Summary of doParseGroupBy
     * @param mixed $groupby
     * @return mixed
     */
    protected function doParseGroupBy($groupby)
    {
        return null;
    }

    /**
     * Summary of doCountAggregates
     * @param mixed $tablename
     * @param mixed $queryfields
     * @param mixed $where
     * @param mixed $groupby
     * @return mixed
     */
    protected function doCountAggregates($tablename, $queryfields, $where = null, $groupby = null)
    {
        return null;
    }

    /**
     * Summary of doPrepareStatement
     * @param mixed $sql
     * @return PDOStatement|bool|mixed
     */
    protected function doPrepareStatement($sql)
    {
        $this->connect();
        return $this->db->prepare($sql);
    }

    /**
     * Summary of doGetLastId
     * @param mixed $table
     * @return bool|mixed|string
     */
    protected function doGetLastId($table = null)
    {
        $this->connect();
        // pgsql expects the sequence name, everyone else expects null
        return $this->db->lastInsertId();
    }

    /**
     * Summary of doGetDatabaseInfo
     * @return mixed
     */
    protected function doGetDatabaseInfo()
    {
        $this->connect();
        // let's re-use PDODatabaseInfo() from xarPDO here :-)
        return new PDODatabaseInfo($this->db);
    }

    /**
     * Summary of doDeleteAll
     * @param string $tablename
     * @return mixed
     */
    protected function doDeleteAll($tablename)
    {
        $this->connect();
        $sql = "DELETE FROM $tablename";
        $stmt = $this->prepareStatement($sql);
        $result = $stmt->execute();
        if (empty($result)) {
            return false;
        }
        return true;
    }
}
