<?php
/**
 * External datastore for DD objects using MongoDB connection from ExternalDatabase
 *
 * Note: this assumes you install the MongoDB PHP Library with composer
 *
 * $ composer require mongodb/mongodb
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
 * External datastore for DD objects using MongoDB connection from ExternalDatabase
 * ```
 * // @todo simplify config someday...
 * $config = [
 *     'dbConnIndex' => 1,
 *     'dbConnArgs' => [
 *         'external' => 'mongodb',
 *         // ...
 *     ],
 * ];
 * $config['dbConnArgs'] = json_encode($config['dbConnArgs']);
 * ```
 */
class MongoDBDataStore extends ExternalDataStore
{
    public const ID_SIZE = 24;
    /** @var \MongoDB\Database|null */
    protected $db     = null;

    /**
     * Summary of getObjectId
     * @param mixed $itemid
     * @return mixed
     */
    public function getObjectId($itemid)
    {
        if (is_string($itemid) && strlen($itemid) == static::ID_SIZE) {
            return new \MongoDB\BSON\ObjectId($itemid);
        }
        return $itemid;
    }

    /**
     * Summary of getItemId
     * @param mixed $objectid
     * @return mixed
     */
    public function getItemId($objectid)
    {
        if (is_object($objectid)) {
            return (string) $objectid;
        }
        return $objectid;
    }

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
        $collection = $this->db->selectCollection($tablename);

        // @todo use projection with $queryfields
        //$result = $collection->findOne([$wherefield => $itemid]);
        $objectid = $this->getObjectId($itemid);
        $result = $collection->findOne(['_id' => $objectid]);
        if (empty($result)) {
            return null;
        }
        /** @var \MongoDB\Model\BSONDocument $result */
        $result['_id'] = $this->getItemId($result['_id']);
        return $result->getArrayCopy();
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
        // see also https://www.mongodb.com/docs/manual/reference/method/ObjectId/
        if (!empty($itemid) && !empty($wherefield)) {
            $values[$wherefield] = $itemid;
        }
        $this->connect();
        $collection = $this->db->selectCollection($tablename);

        // set _id up-front in all cases
        if (empty($itemid)) {
            $objectid = new \MongoDB\BSON\ObjectId();
            if (!empty($wherefield)) {
                $values[$wherefield] = (string) $objectid;
            }
            $values['_id'] = $objectid;
        } else {
            $values['_id'] = $this->getObjectId($itemid);
        }
        $result = $collection->insertOne($values);
        if (empty($result->getInsertedCount())) {
            return null;
        }
        //$itemid ??= $this->getLastId($result);
        $objectid = $result->getInsertedId();
        return $this->getItemId($objectid);
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
        $collection = $this->db->selectCollection($tablename);

        //$result = $collection->updateOne([$wherefield => $itemid], ['$set' => $values]);
        $objectid = $this->getObjectId($itemid);
        $result = $collection->updateOne(['_id' => $objectid], ['$set' => $values]);
        if (empty($result->getMatchedCount())) {
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
        $collection = $this->db->selectCollection($tablename);

        //$result = $collection->deleteOne([$wherefield => $itemid]);
        $objectid = $this->getObjectId($itemid);
        $result = $collection->deleteOne(['_id' => $objectid]);
        if (empty($result->getDeletedCount())) {
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
        $collection = $this->db->selectCollection($tablename);

        $result = [];
        //$cursor = $collection->find(array_combine($where, $params));
        $cursor = $collection->find();
        foreach ($cursor as $document) {
            /** @var \MongoDB\Model\BSONDocument $document */
            $document['_id'] = $this->getItemId($document['_id']);
            //$result[$document['_id']] = $document;
            $result[] = $document->getArrayCopy();
        }
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
        $collection = $this->db->selectCollection($tablename);

        if (empty($where)) {
            $result = $collection->estimatedDocumentCount();
        } else {
            //$result = $collection->countDocuments(array_combine($where, $params));
            $result = $collection->countDocuments();
        }
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
     * @return bool|mixed
     */
    protected function doPrepareStatement($sql)
    {
        throw new \Exception('Are you looking for insertMany/updateMany/deleteMany() or bulkWrite() here?');
    }

    /**
     * Summary of doGetLastId
     * @param mixed $result
     * @return bool|mixed|string
     */
    protected function doGetLastId($result = null)
    {
        /** @var \MongoDB\InsertOneResult $result */
        // already called directly in doCreateItem()
        return $result->getInsertedId();
    }

    /**
     * Summary of doGetDatabaseInfo
     * @return mixed
     */
    protected function doGetDatabaseInfo()
    {
        $this->connect();
        // someone will get a surprise if they don't expect this :-)
        return $this->db->getManager();
    }

    /**
     * Summary of doDeleteAll
     * @param string $tablename
     * @return mixed
     */
    protected function doDeleteAll($tablename)
    {
        $this->connect();
        $result = $this->db->dropCollection($tablename);
        if (empty($result) || empty($result['ok'])) {
            return false;
        }
        return true;
    }
}
