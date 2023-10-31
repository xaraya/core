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
        if (is_numeric($itemid)) {
            return (int) $itemid;
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
        if (is_numeric($objectid)) {
            return (int) $objectid;
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
        $projection = $this->doGetProjection();

        //$result = $collection->findOne([$wherefield => $itemid]);
        $objectid = $this->getObjectId($itemid);
        if (!empty($projection)) {
            $result = $collection->findOne(['_id' => $objectid], ['projection' => $projection]);
        } else {
            $result = $collection->findOne(['_id' => $objectid]);
        }
        if (empty($result)) {
            return null;
        }
        /** @var \MongoDB\Model\BSONDocument $result */
        $result['_id'] = $this->getItemId($result['_id']);
        return $result->getArrayCopy();
    }

    /**
     * Summary of doGetProjection
     * @return array<string, mixed>
     */
    public function doGetProjection()
    {
        $projection = [];
        // @todo evaluate if we always want to use projection, or only when we have field subsets
        if (empty($this->object->fieldsubset)) {
            return $projection;
        }
        foreach ($this->object->getFieldList() as $fieldname) {
            $property = $this->object->properties[$fieldname];
            [$tablename, $field] = explode('.', $property->source);
            if (array_key_exists($fieldname, $this->object->fieldsubset)) {
                foreach ($this->object->fieldsubset[$fieldname] as $key => $subset) {
                    $projection[$field . '.' . $subset] = 1;
                }
                if (property_exists($property, 'subset')) {
                    $property->subset = $this->object->fieldsubset[$fieldname];
                }
            } else {
                $projection[$field] = 1;
            }
        }
        return $projection;
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
        if (array_key_exists('_id', $values)) {
            unset($values['_id']);
        }
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

        $options = [];
        if ($numitems > 0) {
            $options['limit'] = (int) $numitems;
        }
        if ($startnum > 1) {
            $options['skip'] = (int) $startnum - 1;
        }
        if (!empty($sort)) {
            $options['sort'] = $sort;
        }
        // @todo use projection with $queryfields
        //if (!empty($queryfields)) {
        //    $options['projection'] = $queryfields;
        //}
        $projection = $this->doGetProjection();
        if (!empty($projection)) {
            $options['projection'] = $projection;
        }

        $result = [];
        //$cursor = $collection->find(array_combine($where, $params));
        if (!empty($itemids)) {
            // map to objectid or int
            $itemids = array_map([$this, 'getObjectId'], $itemids);
            $cursor = $collection->find(['_id' => ['$in' => $itemids]], $options);
        } elseif (!empty($where)) {
            $cursor = $collection->find($where, $options);
        } else {
            $cursor = $collection->find([], $options);
        }
        foreach ($cursor as $document) {
            /** @var \MongoDB\Model\BSONDocument $document */
            $document['_id'] = $this->getItemId($document['_id']);
            //$result[$document['_id']] = $document;
            $result[] = $document->getArrayCopy();
        }
        return $result;
    }

    /**
     * Summary of doParseWhere
     * @param mixed $where
     * @return mixed
     */
    protected function doParseWhere($where)
    {
        if (empty($where)) {
            return null;
        }
        $filter = [];
        $orquery = false;
        // this only supports AND-ing clauses for different fields - see OR-ing below
        foreach ($where as $whereitem) {
            if (empty($whereitem)) {
                continue;
            }
            // $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . 'dd_' . $whereitem['field'] . ' ' . $whereitem['clause'] . $whereitem['post'] . ' ';
            $fieldname = $whereitem['name'];
            if (empty($this->object->properties[$fieldname])) {
                throw new \Exception('Invalid where fieldname ' . $fieldname);
            }
            $property = $this->object->properties[$fieldname];
            if (empty($property->source)) {
                throw new \Exception('Invalid where property ' . $fieldname);
            }
            [$tablename, $field] = explode('.', $property->source);
            $clause = $this->doParseWhereClause($whereitem['clause'], $property->basetype);
            // db.bios.find( { birth: { $gt: new Date('1940-01-01'), $lt: new Date('1960-01-01') } } )
            if (!array_key_exists($field, $filter)) {
                $filter[$field] = $clause;
            } elseif (is_array($filter[$field]) && is_array($clause)) {
                $filter[$field] = array_merge($filter[$field], $clause);
            } elseif (is_array($filter[$field])) {
                $filter[$field] = array_merge($filter[$field], ['$eq' => $clause]);
            } elseif (is_array($clause)) {
                $filter[$field] = array_merge(['$eq' => $filter[$field]], $clause);
            } else {
                // now that's just weird
                throw new \Exception('Unable to merge $eq clauses for ' . $field);
            }
            if ($whereitem['join'] === 'or') {
                $orquery = true;
            }
        }
        // assume it's all or nothing - see search.php
        if ($orquery) {
            $final = ['$or' => []];
            foreach ($filter as $field => $clause) {
                $final['$or'][] = [$field => $clause];
            }
            $filter = $final;
        }
        return $filter;
    }

    /**
     * Summary of doParseWhereClause
     * @param mixed $clause
     * @param mixed $basetype see DataProperty::showFilter()
     * @return mixed
     */
    public function doParseWhereClause($clause, $basetype)
    {
        // see https://www.mongodb.com/docs/manual/reference/method/db.collection.find/
        $clause = trim($clause);
        [$op, $value] = explode(' ', $clause, 2);
        $op = strtolower($op);
        // parse clause: IN (2,3,4) - see loader.php
        if ($op === 'in') {
            $values = explode(',', trim($value, '()'));
            // @todo improve type checking
            if (is_string($values[0])) {
                if (strlen($values[0]) == static::ID_SIZE) {
                    // objectid format - do not convert here
                } elseif (is_numeric($values[0])) {
                    // for floatbox
                    if ($basetype == 'float') {
                        $values = array_map('floatval', $values);
                    //} elseif ($basetype == 'checkbox') {
                    //    $values = array_map('boolval', $values);
                    } else {
                        $values = array_map('intval', $values);
                    }
                } else {
                    // @todo trim single quotes around values?
                }
            }
            // db.bios.find( { contribs: { $in: [ "ALGOL", "Lisp" ]} } )
            return ['$in' => $values];
        }
        // parse clause: = 2 - see loader.php
        if ($op === '=') {
            // @todo improve type checking
            if (is_string($value)) {
                if (strlen($value) == static::ID_SIZE) {
                    // objectid format - do not convert here
                } elseif (is_numeric($value)) {
                    // for floatbox
                    if ($basetype == 'decimal') {
                        $value = floatval($value);
                    //} elseif ($basetype == 'checkbox') {
                    //    $value = boolval($value);
                    } else {
                        $value = intval($value);
                    }
                } else {
                    // @todo trim single quotes around value?
                }
            }
            // db.bios.find( { "name.last": "Hopper" } )
            return $value;
        }
        // see https://www.mongodb.com/docs/manual/reference/operator/query/#query-selectors
        $mapop = ['!=' => '$ne', '>' => '$gt', '<' => '$lt', '>=' => '$lte', '<=' => '$gte'];
        if (!empty($mapop[$op])) {
            // @todo improve type checking
            if (is_string($value)) {
                if (strlen($value) == static::ID_SIZE) {
                    // objectid format - do not convert here
                } elseif (is_numeric($value)) {
                    // for floatbox
                    if ($basetype == 'decimal') {
                        $value = floatval($value);
                    //} elseif ($basetype == 'checkbox') {
                    //    $value = boolval($value);
                    } else {
                        $value = intval($value);
                    }
                } else {
                    // @todo trim single quotes around value?
                }
            }
            // db.collection.find( { qty: { $gt: 4 } } )
            return [$mapop[$op] => $value];
        }
        // like see https://sparkbyexamples.com/mongodb/mongodb-query-like/
        // db.bios.find( { "name.last": { $regex: /^N/ } } )
        // 'city' => ['$regex' => '^garden', '$options' => 'i'],
        // parse clause: LIKE '%Holmes%' - see search.php
        if ($op === 'like') {
            if (str_starts_with($value, "'%")) {
                $value = str_replace("'%", "", $value);
            } else {
                $value = '^' . substr($value, 1);
            }
            if (str_ends_with($value, "%'")) {
                $value = str_replace("%'", "", $value);
            } else {
                $value = substr($value, 0, strlen($value) - 1) . '$';
            }
            return ['$regex' => $value];
        }
        // @todo parse other clauses
        throw new \Exception('Unsupported operation for ' . $clause);
        //return [];
    }

    /**
     * Summary of doParseSort
     * @param mixed $sort
     * @return mixed
     */
    protected function doParseSort($sort)
    {
        if (empty($sort)) {
            return null;
        }
        $sortfields = [];
        foreach ($sort as $sortitem) {
            if (empty($sortitem)) {
                continue;
            }
            // $query .= $join . 'dd_' . $sortitem['field'] . ' ' . $sortitem['sortorder'];
            $fieldname = $sortitem['name'];
            if (empty($this->object->properties[$fieldname])) {
                throw new \Exception('Invalid sort fieldname ' . $fieldname);
            }
            $property = $this->object->properties[$fieldname];
            if (empty($property->source)) {
                throw new \Exception('Invalid sort property ' . $fieldname);
            }
            [$tablename, $field] = explode('.', $property->source);
            if (strtoupper($sortitem['sortorder']) == 'DESC') {
                $sortfields[$field] = -1;
            } else {
                $sortfields[$field] = 1;
            }
        }
        return $sortfields;
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

        $options = [];
        if (empty($itemids) && empty($where)) {
            $result = $collection->estimatedDocumentCount();
        } elseif (!empty($itemids)) {
            // map to objectid or int
            $itemids = array_map([$this, 'getObjectId'], $itemids);
            $result = $collection->countDocuments(['_id' => ['$in' => $itemids]]);
        } elseif (!empty($where)) {
            //$result = $collection->countDocuments(array_combine($where, $params));
            $result = $collection->countDocuments($where);
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
