<?php
/**
 * External datastore for DD objects using Doctrine DBAL connection from ExternalDatabase
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

use sys;

sys::import('xaraya.datastores.external');

/**
 * External datastore for DD objects using Doctrine DBAL connection from ExternalDatabase
 * ```
 * // @todo simplify config someday...
 * $config = [
 *     'dbConnIndex' => 1,
 *     'dbConnArgs' => [
 *         'external' => 'dbal',
 *         // ...
 *     ],
 * ];
 * $config['dbConnArgs'] = json_encode($config['dbConnArgs']);
 * ```
 */
class DbalDataStore extends ExternalDataStore
{
    /** @var \Doctrine\DBAL\Connection|null */
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
        $qb = $this->db->createQueryBuilder()
            ->add('select', $queryfields)  // can't use ->select() here with array of fields
            ->from($tablename)
            ->where($wherefield . ' = ?');
        $sql = $qb->getSQL();
        //echo $sql;
        $params = [$itemid];

        $result = $this->db->fetchAssociative($sql, $params);
        if ($result === false) {
            return null;
        }
        return $result;
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
        $result = $this->db->insert($tablename, $values);
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
        $result = $this->db->update($tablename, $values, [$wherefield => $itemid]);
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
        $result = $this->db->delete($tablename, [$wherefield => $itemid]);
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
        $qb = $this->db->createQueryBuilder()
            ->add('select', $queryfields)  // can't use ->select() here with array of fields
            ->from($tablename);
        // @todo support where etc.
        $params = [];
        /**
        if (!empty($where)) {
            $qb->where(
                $qb->expr()->and(
                    $qb->expr()->eq('username', '?'),
                    $qb->expr()->eq('email', '?')
                )
            );
        }
         */
        $sql = $qb->getSQL();
        //echo $sql;

        // Note: we could use iterateAssociative() here as optimization
        $result = $this->db->fetchAllAssociative($sql, $params);
        return $result;
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

        $qb = $this->db->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($tablename);
        // @todo support where etc.
        $params = [];
        /**
        if (!empty($where)) {
            $qb->where(
                $qb->expr()->and(
                    $qb->expr()->eq('username', '?'),
                    $qb->expr()->eq('email', '?')
                )
            );
        }
         */
        $sql = $qb->getSQL();
        //echo $sql;

        $result = $this->db->fetchOne($sql, $params);
        return (int) $result;
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
     * @return \Doctrine\DBAL\Statement|bool|mixed
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
        // someone will get a surprise if they don't expect this :-)
        return $this->db->createSchemaManager();
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
        $result = $this->db->executeStatement($sql);
        if (empty($result)) {
            return false;
        }
        return true;
    }
}
