<?php
/**
 * External SQL-like datastore for DD objects unrelated to Xaraya database(s) or DB methods
 *
 * This relies on the ExternalDatabase, and each type of connection (PDO/DBAL/...)
 * will need to use its specific native methods in the do*() methods below
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

use Xaraya\Database\ExternalDatabase;
use DataPropertyMaster;
use sys;

sys::import('xaraya.datastores.sql');
sys::import('xaraya.database.external');

/**
 * External SQL-like datastore for DD objects unrelated to Xaraya database(s) or DB methods
 *
 * This can be extended for database access using PHP PDO, Doctrine DBAL, MongoDB, ...
 * as long as ExternalDatabase can provide the connection and you adapt the
 * do*() methods below to use the native methods for that connection type
 */
abstract class ExternalDataStore extends SQLDataStore
{
    /** @var string */
    private static $_deferred_property = 'DeferredItemProperty';
    /** @var array<string, mixed> */
    public static $argsToIndex = [];
    /** @var array<string, mixed> */
    public $dbConnArgs = [];
    /** @var string */
    public $tableName = '';

    /**
     * Summary of __construct
     * @param mixed $name
     * @param int|string $dbConnIndex connection index of the external database (if already connected)
     * @param array<string, mixed> $dbConnArgs connection parameters to the external database
     */
    public function __construct($name = null, $dbConnIndex = '', $dbConnArgs = [])
    {
        parent::__construct($name, $dbConnIndex);
        $this->dbConnArgs = $dbConnArgs;
    }

    /**
     * Summary of __toString
     * @return string
     */
    public function __toString()
    {
        return "external";
    }

    /**
     * Summary of getItem
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return mixed
     */
    public function getItem(array $args = [])
    {
        // Bail if the object has no properties
        if (count($this->object->properties) < 1) {
            return;
        }
        // Make sure we have a primary field
        if (empty($this->object->primary)) {
            throw new \Exception('The object ' . $this->object->name . ' has no primary key');
        }

        // Get the itemid from the params or from the object definition
        $itemid = $args['itemid'] ?? $this->object->itemid;

        // Build the query field list
        $fieldlist = $this->object->getFieldList();
        $fieldname = $this->object->primary;
        $property = $this->object->properties[$fieldname];
        [$tablename, $wherefield] = explode('.', $property->source);
        // Make sure we include the primary key, even if it won't be displayed
        if (!in_array($fieldname, $fieldlist)) {
            array_unshift($fieldlist, $fieldname);
        }
        $queryfields = [];
        foreach ($fieldlist as $fieldname) {
            $property = $this->object->properties[$fieldname];
            if (empty($property->source)) {
                continue;
            }
            $queryfields[] = $property->source . ' as ' . $fieldname;
        }

        // Get item
        $item = $this->doGetItem($itemid, $tablename, $queryfields, $wherefield);
        if (empty($item)) {
            return null;
        }

        foreach ($fieldlist as $fieldname) {
            // Note: no subitems supported at the moment
            if (is_subclass_of($this->object->properties[$fieldname], self::$_deferred_property)) {
                // Is this a deferred item property or one of its subclasses?
                $this->object->properties[$fieldname]->setValue($item[$fieldname] ?? null);
            } elseif (empty($this->object->properties[$fieldname]->source)) {
                // This is some other property with a virtual datasource, ignore it
            } elseif (array_key_exists($fieldname, $item)) {
                // This is a property with a normal datasource: assign the value in the usual way
                $this->object->properties[$fieldname]->setValue($item[$fieldname]);
            } else {
                // Keep default value
            }
        }

        return $itemid;
    }

    /**
     * Summary of doGetItem
     * @param mixed $itemid
     * @param string $tablename
     * @param array<string> $queryfields
     * @param string $wherefield
     * @return mixed
     */
    abstract protected function doGetItem($itemid, $tablename, $queryfields, $wherefield);

    /**
     * Summary of doCollectValues
     * @param mixed $itemid
     * @param string $tablename
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    protected function doCollectValues($itemid, $tablename, $args = [])
    {
        $values = [];
        foreach ($this->object->fieldlist as $fieldname) {
            $property = $this->object->properties[$fieldname];
            if (empty($property->source)) {
                // Ignore fields with no source
                continue;
            }
            [$table, $field] = explode('.', $property->source);
            if ($table !== $tablename) {
                // Ignore the fields from tables that are foreign - single-table insert/update here!
                continue;
            } elseif (array_key_exists($fieldname, $args)) {
                // We have an override through the method's parameters
                $values[$field] = $args[$fieldname];
            } elseif ($property->getInputStatus() == DataPropertyMaster::DD_INPUTSTATE_IGNORED) {
                // Ignore the fields with IGNORE status
                continue;
            } elseif ($fieldname == $this->object->primary) {
                // Ignore the primary value if not set
                if (!isset($itemid)) {
                    continue;
                }
                $values[$field] = $itemid;
            } else {
                // No override, just take the value the property already has
                if ($property->basetype == 'checkbox' && str_contains($property->configuration, 'tinyint')) {
                    $values[$field] = (int) $property->value;
                } else {
                    $values[$field] = $property->value;
                }
            }
        }
        return $values;
    }

    /**
     * Create an item in the flat table
     *
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return mixed
     **/
    public function createItem(array $args = [])
    {
        // Bail if the object has no properties
        if (count($this->object->properties) < 1) {
            return null;
        }
        // Make sure we have a primary field
        if (empty($this->object->primary)) {
            throw new \Exception('The object ' . $this->object->name . ' has no primary key');
        }

        // Get the itemid from the params or from the object definition
        $itemid = $args['itemid'] ?? $this->object->itemid;

        // If no itemid was passed or found on the object, get the next id (or dummy)
        if (empty($itemid)) {
            $itemid = null;
        }

        $fieldname = $this->object->primary;
        $property = $this->object->properties[$fieldname];
        [$tablename, $wherefield] = explode('.', $property->source);

        $values = $this->doCollectValues($itemid, $tablename, $args);
        if (empty($values)) {
            return $itemid;
        }

        // create item
        $result = $this->doCreateItem($itemid, $tablename, $values, $wherefield);
        // set the last inserted id
        if (!isset($itemid) && !empty($result)) {
            $this->object->properties[$this->object->primary]->value = $result;
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
    abstract protected function doCreateItem($itemid, $tablename, $values, $wherefield = '');

    /**
     * Summary of updateItem
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return mixed
     */
    public function updateItem(array $args = [])
    {
        // Bail if the object has no properties
        if (count($this->object->properties) < 1) {
            return null;
        }
        // Make sure we have a primary field
        if (empty($this->object->primary)) {
            throw new \Exception('The object ' . $this->object->name . ' has no primary key');
        }

        // Get the itemid from the params or from the object definition
        $itemid = $args['itemid'] ?? $this->object->itemid;

        $fieldname = $this->object->primary;
        $property = $this->object->properties[$fieldname];
        [$tablename, $wherefield] = explode('.', $property->source);

        $values = $this->doCollectValues($itemid, $tablename, $args);
        if (empty($values)) {
            return $itemid;
        }

        // update item
        $result = $this->doUpdateItem($itemid, $tablename, $values, $wherefield);
        return $result;
    }

    /**
     * Summary of doUpdateItem
     * @param mixed $itemid
     * @param string $tablename
     * @param array<string, mixed> $values
     * @param string $wherefield
     * @return mixed
     */
    abstract protected function doUpdateItem($itemid, $tablename, $values, $wherefield);

    /**
     * Summary of deleteItem
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return mixed
     */
    public function deleteItem(array $args = [])
    {
        // Bail if the object has no properties
        if (count($this->object->properties) < 1) {
            return null;
        }
        // Make sure we have a primary field
        if (empty($this->object->primary)) {
            throw new \Exception('The object ' . $this->object->name . ' has no primary key');
        }

        // Get the itemid from the params or from the object definition
        $itemid = $args['itemid'] ?? $this->object->itemid;

        $fieldname = $this->object->primary;
        $property = $this->object->properties[$fieldname];
        [$tablename, $wherefield] = explode('.', $property->source);

        // delete item
        $result = $this->doDeleteItem($itemid, $tablename, $wherefield);
        return $result;
    }

    /**
     * Summary of doDeleteItem
     * @param mixed $itemid
     * @param string $tablename
     * @param string $wherefield
     * @return mixed
     */
    abstract protected function doDeleteItem($itemid, $tablename, $wherefield);

    /**
     * Summary of getItems
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return void
     */
    public function getItems(array $args = [])
    {
        // Bail if the object has no properties
        if (count($this->object->properties) < 1) {
            return;
        }
        // Make sure we have a primary field (not required here)
        //if (empty($this->object->primary)) {
        //    throw new Exception('The object ' . $this->object->name . ' has no primary key');
        //}

        // @todo support aggregate functions with groupby someday
        if (!empty($this->object->groupby)) {
            $this->getAggregates();
            return;
        }

        if (!empty($args['numitems'])) {
            $numitems = (int) $args['numitems'];
        } else {
            $numitems = 0;
        }
        if (!empty($args['startnum'])) {
            $startnum = (int) $args['startnum'];
        } else {
            $startnum = 1;
        }
        if (!empty($args['itemids'])) {
            $itemids = $args['itemids'];
            // random: removing this as it injects prior results (?) into the current query (see addDataStore method in master.php)
            //		   itemids should be passed via $args['itemids']
            //        } elseif (isset($this->_itemids)) {
            //            $itemids = $this->_itemids;
        } else {
            $itemids = [];
        }
        $saveids = 0;
        if (count($itemids) == 0) {
            $saveids = 1;
        }

        // Build the query field list
        $fieldlist = $this->object->getFieldList();
        // Make sure we include the primary key, even if it won't be displayed (not required here)
        if (!empty($this->object->primary)) {
            $fieldname = $this->object->primary;
            if (!in_array($fieldname, $fieldlist)) {
                array_unshift($fieldlist, $fieldname);
            }
        }
        $tablename = '';
        $queryfields = [];
        foreach ($fieldlist as $fieldname) {
            $property = $this->object->properties[$fieldname];
            if (empty($property->source)) {
                continue;
            }
            // pick the first property that has a source for the tablename here
            if (empty($tablename)) {
                [$tablename, $field] = explode('.', $property->source);
            }
            $queryfields[] = $property->source . ' as ' . $fieldname;
        }
        // @todo select based on whatever criteria were passed here
        $where = null;
        if (empty($itemids)) {
            $where = $this->doParseWhere($this->object->ddwhere);
        }
        $sort = $this->doParseSort($this->object->ddsort);

        // Get items
        $result = $this->doGetItems($itemids, $tablename, $queryfields, $where, $sort, $startnum, $numitems);
        if (empty($result)) {
            return;
        }

        foreach ($result as $key => $item) {
            if (!empty($this->object->primary) && isset($item[$this->object->primary])) {
                // Get the value of the primary key
                $itemid = $item[$this->object->primary];
            } else {
                // No primary field: use the row key
                $itemid = $key;
            }
            // Add this itemid to the list
            if ($saveids && isset($itemid)) {
                $this->_itemids[] = $itemid;
            }
            // Set the values of the valid properties
            foreach ($fieldlist as $fieldname) {
                // Note: no subitems supported at the moment
                if (is_subclass_of($this->object->properties[$fieldname], self::$_deferred_property)) {
                    // Is this a deferred item property or one of its subclasses?
                    $this->object->properties[$fieldname]->setItemValue($itemid, $item[$fieldname] ?? null);
                } elseif (empty($this->object->properties[$fieldname]->source)) {
                    // This is some other property with a virtual datasource, ignore it
                } elseif (array_key_exists($fieldname, $item)) {
                    // This is a  property with a normal datasource: assign the value in the usual way
                    $this->object->properties[$fieldname]->setItemValue($itemid, $item[$fieldname]);
                } else {
                    // Keep default value
                }
            }
        }
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
    abstract protected function doGetItems($itemids, $tablename, $queryfields, $where = null, $sort = null, $startnum = 1, $numitems = 0);

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
        $wherestring = '';
        foreach ($where as $whereitem) {
            if (empty($whereitem)) {
                continue;
            }
            $fieldname = $whereitem['name'];
            if (empty($this->object->properties[$fieldname])) {
                throw new \Exception('Invalid where fieldname ' . $fieldname);
            }
            $property = $this->object->properties[$fieldname];
            if (empty($property->source)) {
                throw new \Exception('Invalid where property ' . $fieldname);
            }
            $wherestring .= $whereitem['join'] . ' ' . $whereitem['pre'] . $property->source . ' ' . $whereitem['clause'] . $whereitem['post'] . ' ';
        }
        return $wherestring;
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
        $sortstring = '';
        $join = '';
        foreach ($sort as $sortitem) {
            if (empty($sortitem)) {
                continue;
            }
            $fieldname = $sortitem['name'];
            if (empty($this->object->properties[$fieldname])) {
                throw new \Exception('Invalid sort fieldname ' . $fieldname);
            }
            $property = $this->object->properties[$fieldname];
            if (empty($property->source)) {
                throw new \Exception('Invalid sort property ' . $fieldname);
            }
            $sortstring .= $join . $property->source . ' ' . $sortitem['sortorder'];
            $join = ', ';
        }
        return $sortstring;
    }

    /**
     * Summary of countItems
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return int|null
     */
    public function countItems($args = [])
    {
        if (!empty($args['itemids'])) {
            $itemids = $args['itemids'];
        } elseif (isset($this->_itemids)) {
            $itemids = $this->_itemids;
        } else {
            $itemids = [];
        }

        $tablename = '';
        if (!empty($this->object->primary)) {
            $fieldname = $this->object->primary;
            $property = $this->object->properties[$fieldname];
            [$tablename, $field] = explode('.', $property->source);
        } else {
            $fieldlist = $this->object->getFieldList();
            foreach ($fieldlist as $fieldname) {
                $property = $this->object->properties[$fieldname];
                if (empty($property->source)) {
                    continue;
                }
                // pick the first property that has a source for the tablename here
                [$tablename, $field] = explode('.', $property->source);
                break;
            }
        }

        // @todo select based on whatever criteria were passed here
        $where = null;
        if (empty($itemids)) {
            $where = $this->doParseWhere($this->object->ddwhere);
        }

        // count items
        return $this->doCountItems($itemids, $tablename, $where);
    }

    /**
     * Summary of doCountItems
     * @param array<int> $itemids
     * @param string $tablename
     * @param mixed $where
     * @return int
     */
    abstract protected function doCountItems($itemids, $tablename, $where = null);

    /**
     * Summary of getItems
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return void
     */
    public function getAggregates(array $args = [])
    {
        $groupby = $this->doParseGroupBy($this->object->groupby);
        return;
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
    abstract protected function doGetAggregates($itemids, $tablename, $queryfields, $where = null, $sort = null, $groupby = null, $startnum = 1, $numitems = 0);

    /**
     * Summary of doParseGroupBy
     * @param mixed $groupby
     * @return mixed
     */
    abstract protected function doParseGroupBy($groupby);

    /**
     * Summary of countAggregates
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return int|null
     */
    public function countAggregates($args = [])
    {
        $groupby = $this->doParseGroupBy($this->object->groupby);
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
    abstract protected function doCountAggregates($tablename, $queryfields, $where = null, $groupby = null);

    /**
     * Database functions for lazy connection from ExternalDatabase
     * @return void
     */
    protected function connect()
    {
        // connect database
        if (empty($this->db)) {
            $this->doConnect();
        }
    }

    /**
     * Summary of doConnect - this is *not* overridden for PDO, DBAL etc.
     * @return void
     */
    protected function doConnect()
    {
        if (!empty($this->dbConnIndex) && !is_numeric($this->dbConnIndex)) {
            $this->db = ExternalDatabase::getConn($this->dbConnIndex);
            return;
        }
        // @todo use ExternalDatabase::checkDbConnection() here too?
        $key = ExternalDatabase::INDEX_PREFIX . md5(serialize($this->dbConnArgs));
        if (isset(static::$argsToIndex[$key])) {
            $dbConnIndex = static::$argsToIndex[$key];
            $this->db = ExternalDatabase::getConn($dbConnIndex);
            return;
        }
        $this->db = ExternalDatabase::newConn($this->dbConnArgs);
        $dbConnIndex = ExternalDatabase::getConnIndex();
        static::$argsToIndex[$key] = $dbConnIndex;
    }

    /**
     * Summary of prepareStatement - for internal use only
     * @param mixed $sql
     * @return mixed
     */
    protected function prepareStatement($sql)
    {
        // prepare sql statement and return *something*
        return $this->doPrepareStatement($sql);
    }

    /**
     * Summary of doPrepareStatement
     * @param mixed $sql
     * @return \PDOStatement|\Doctrine\DBAL\Statement|bool|mixed
     */
    abstract protected function doPrepareStatement($sql);

    /**
     * Summary of getLastId - for internal use only
     * @param mixed $table
     * @return mixed
     */
    protected function getLastId($table)
    {
        // get last id
        return $this->doGetLastId($table);
    }

    /**
     * Summary of doGetLastId
     * @param mixed $table
     * @return bool|mixed|string
     */
    abstract protected function doGetLastId($table = null);

    /**
     * Summary of getDatabaseInfo - this will return *something*
     * @return mixed
     */
    public function getDatabaseInfo()
    {
        // get database info
        return $this->doGetDatabaseInfo();
    }

    /**
     * Summary of doGetDatabaseInfo
     * @return mixed
     */
    abstract protected function doGetDatabaseInfo();

    /**
     * Summary of deleteAll
     * @param string $tablename
     * @return mixed
     */
    public function deleteAll($tablename)
    {
        return $this->doDeleteAll($tablename);
    }

    /**
     * Summary of doDeleteAll
     * @param string $tablename
     * @return mixed
     */
    abstract protected function doDeleteAll($tablename);

    /**
     * Summary of getConnection
     * @return \Connection|object|\PDOConnection
     */
    public function getConnection()
    {
        $this->connect();
        return $this->db;
    }

    /**
     * Class method to get a new external data store (of the right type)
     * @param string $name
     * @param int|string|null $dbConnIndex connection index of the database if different from Xaraya DB
     * @param ?array<string, mixed> $dbConnArgs connection params of the database if different from Xaraya DB
     * @return IBasicDataStore
     */
    public static function getDataStore($name = 'external', $dbConnIndex = '', $dbConnArgs = [])
    {
        // re-use external db connection
        if (!empty($dbConnIndex) && !is_numeric($dbConnIndex)) {
            $driver = ExternalDatabase::getDriverName($dbConnIndex);
        } else {
            $driver = $dbConnArgs['external'];
            $dbConnIndex = '';
        }
        switch ($driver) {
            case 'dbal':
                sys::import('xaraya.datastores.external.dbal');
                $datastore = new DbalDataStore($name, $dbConnIndex, $dbConnArgs);
                break;
            case 'mongodb':
                sys::import('xaraya.datastores.external.mongodb');
                $datastore = new MongoDBDataStore($name, $dbConnIndex, $dbConnArgs);
                break;
            case 'pdo':
                sys::import('xaraya.datastores.external.pdo');
                $datastore = new PdoDataStore($name, $dbConnIndex, $dbConnArgs);
                break;
            default:
                throw new \Exception('Unknown database driver ' . $driver);
        }
        return $datastore;
    }
}
