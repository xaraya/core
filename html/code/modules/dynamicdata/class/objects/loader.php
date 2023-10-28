<?php
/**
 * The DataObject Loader delays loading values from the database until they are requested.
 * It was inspired by how GraphQL-PHP tackles the N+1 problem, but without proxy, callable or promises (sync or async).
 * Right now this would be the equivalent of lazy loading in batch for showOutput() in object lists :-)
 *
 * Usage:
 * $itemloader = new DataObjectItemLoader('sample', ['name', 'age']);
 * // get list of itemids from somewhere, e.g. a parent list
 * // ...
 * // add itemids to dataloader for delayed loading
 * foreach ($itemids as $id) {
 *     $itemloader->add($id);
 * }
 * // do some more processing
 * // ...
 * // get items from dataloader cache after internal bulk loading
 * foreach ($itemids as $id) {
 *     $item = $itemloader->get($id);
 * }
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 **/

sys::import('modules.dynamicdata.class.objects.master');

class DataObjectLoader
{
    public $objectname = '';
    public $fieldlist = [];
    public $todo = [];
    public $cache = [];
    public $resolver = null;
    public $preLoader = null;
    public $postLoader = null;
    public $checkFieldlist = true;
    public $order = '';
    public $limit = null;
    public $offset = 0;
    public $filter = [];
    public $count = false;
    public $access = null;
    // public $expand = null;
    public $objectlist = null;
    public static $loaders = [];

    public static function getItemLoader(string $objectname = 'sample', array $fieldlist = ['id', 'name'])
    {
        // we don't have an itemloader for this object yet, so we make a new one and keep it
        if (empty(self::$loaders[$objectname])) {
            self::$loaders[$objectname] = new DataObjectItemLoader($objectname, $fieldlist);
            return self::$loaders[$objectname];
        }
        // we already have all the fields we need in the current itemloader, so we use it
        $newfieldlist = array_unique(array_merge(self::$loaders[$objectname]->fieldlist, $fieldlist));
        if (count($newfieldlist) == count(self::$loaders[$objectname]->fieldlist)) {
            return self::$loaders[$objectname];
        }
        // we are missing some fields in the current itemloader, so we make a new one
        return new DataObjectItemLoader($objectname, $fieldlist);
    }

    public function __construct(string $objectname = 'sample', array $fieldlist = ['id', 'name'], ?callable $resolver = null)
    {
        $this->objectname = $objectname;
        $this->fieldlist = $fieldlist;
        $this->todo = [];
        $this->cache = [];
        $this->resolver = $resolver;
        $this->preLoader = null;
        $this->postLoader = null;
        $this->checkFieldlist = true;
    }

    // https://stackoverflow.com/questions/36079651/silence-declaration-should-be-compatible-warnings-in-php-7/36196748
    public function add($value)
    {
        if (is_array($value)) {
            $this->addList($value);
        } else {
            $this->addItem((int)$value);
        }
    }

    public function addItem(int $value)
    {
        if (!$this->hasItem($value)) {
            $this->todo[] = $value;
        }
    }

    public function addList(array $values)
    {
        foreach ($values as $value) {
            $this->addItem((int)$value);
        }
    }

    // https://stackoverflow.com/questions/36079651/silence-declaration-should-be-compatible-warnings-in-php-7/36196748
    public function get($value)
    {
        if (!empty($this->todo)) {
            $this->load();
        }
        if (is_array($value)) {
            return $this->getList($value);
        } else {
            return $this->getItem((int)$value);
        }
    }

    public function getItem(int $value)
    {
        if (!empty($value) &&
            array_key_exists("$value", $this->cache)) {
            return $this->cache["$value"];
        }
    }

    public function getList(array $values)
    {
        $items = [];
        foreach ($values as $value) {
            $key = (string) $value;
            $items[$key] = $this->getItem((int)$value);
        }
        return $items;
    }

    public function hasItem(int $value)
    {
        if (empty($value)) {
            return false;
        } elseif (!in_array($value, $this->todo) &&
            !array_key_exists("$value", $this->cache)) {
            return false;
        }
        return true;
    }

    public function load()
    {
        if (empty($this->todo)) {
            return;
        }
        $this->preLoad();
        // 1. call resolver if defined, or
        if (!empty($this->resolver) && is_callable($this->resolver)) {
            //$this->cache = call_user_func($this->resolver, $this->todo);
            // @checkme use replace instead of merge here
            $this->cache = array_replace($this->cache, call_user_func($this->resolver, $this->todo));
            // 2. get values from objectlist here
        } else {
            //$this->cache = $this->getValues($this->todo);
            // @checkme use replace instead of merge here
            $this->cache = array_replace($this->cache, $this->getValues($this->todo));
        }
        $this->postLoad();
        $this->todo = [];
    }

    public function preLoad()
    {
        if (!empty($this->preLoader) && is_callable($this->preLoader)) {
            $this->todo = call_user_func($this->preLoader, $this->todo);
        }
    }

    public function postLoad()
    {
        if (!empty($this->postLoader) && is_callable($this->postLoader)) {
            $this->cache = call_user_func($this->postLoader, $this->cache);
        }
    }

    public function getValues(array $itemids)
    {
        // Note: itemids may be empty here, e.g. when called from getOptions() in deferitem property
        if (empty($this->objectname)) {
            return [];
        }
        xarLog::message("DataObjectLoader::getValues: get " . count($itemids) . " items from " . $this->objectname, xarLog::LEVEL_INFO);
        $params = ['name' => $this->objectname, 'fieldlist' => $this->fieldlist];
        //$params = array('name' => $this->objectname, 'fieldlist' => $this->fieldlist, 'itemids' => $itemids);
        $this->objectlist = DataObjectMaster::getObjectList($params);
        // @checkme relational objects filter fieldlist param based on status in objectlist constructor?
        $this->objectlist->setFieldList($this->fieldlist);
        // see what DataObjectMaster found with setupFieldList()
        if (empty($this->fieldlist)) {
            $this->fieldlist = $this->objectlist->getFieldList();
        }
        $params = ['itemids' => $itemids];
        $result = $this->objectlist->getItems($params);
        // return array("$itemid" => assoc array of $fields)
        // @checkme variabletable returns all fields at the moment - filter here for now
        //return $result;
        $values = [];
        // key white-list filter - https://www.php.net/manual/en/function.array-intersect-key.php
        $allowed = array_flip($this->fieldlist);
        // @checkme GraphQL standardizes on id, but we may need to adapt for other interfaces
        $allowed['id'] = true;
        //$addPrimary = false;
        //if (!empty($this->objectlist->primary) && !in_array($this->objectlist->primary, $this->fieldlist)) {
        //    $allowed[$this->objectlist->primary] = true;
        //    $addPrimary = true;
        //}
        foreach ($result as $itemid => $props) {
            //if ($addPrimary) {
            //    $props[$this->objectlist->primary] = $itemid;
            //}
            $key = (string) $itemid;
            $values[$key] = array_intersect_key($props, $allowed);
        }
        xarLog::message("DataObjectLoader::getValues: got " . count($values) . " values from " . $this->objectname, xarLog::LEVEL_INFO);
        // return array("$itemid" => single $field value) - see defer* properties
        return $values;
    }

    public function getObjectList(array $params = [])
    {
        if (empty($params)) {
            $params = ['name' => $this->objectname, 'fieldlist' => $this->fieldlist];
            //$params = array('name' => $this->objectname, 'fieldlist' => $this->fieldlist, 'itemids' => $itemids);
        }
        $this->objectlist = DataObjectMaster::getObjectList($params);
        if (!empty($this->access) && !$this->objectlist->checkAccess($this->access)) {
            //http_response_code(403);
            throw new Exception('No access to object ' . $this->objectname);
        }
        // @checkme relational objects filter fieldlist param based on status in objectlist constructor?
        $this->objectlist->setFieldList($this->fieldlist);
        // see what DataObjectMaster found with setupFieldList()
        if (empty($this->fieldlist)) {
            $this->fieldlist = $this->objectlist->getFieldList();
        }
        $this->applyObjectFilter($this->objectlist);
        $this->getCount($this->objectlist);
        return $this->objectlist;
    }

    public function setFieldlist(array $fieldlist)
    {
        $this->fieldlist = $fieldlist;
    }

    public function mergeFieldlist(array $fieldlist)
    {
        if (!empty($fieldlist) && $this->checkFieldlist) {
            $this->fieldlist = array_unique(array_merge($this->fieldlist, $fieldlist));
            $this->checkFieldlist = false;
        }
    }

    public function setOrder(string $order)
    {
        $this->order = $order;
    }

    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }

    public function setOffset(int $offset)
    {
        $this->offset = $offset;
    }

    public function setFilter(array $filter)
    {
        $this->filter = $filter;
    }

    public function addFilter(array $filter)
    {
        array_push($this->filter, $filter);
    }

    public function query(array $args)
    {
        $this->parseQueryArgs($args);
        $objectlist = $this->getObjectList();
        $params = $this->addPagingParams();
        $result = $objectlist->getItems($params);
        $values = [];
        // key white-list filter - https://www.php.net/manual/en/function.array-intersect-key.php
        $allowed = array_flip($this->fieldlist);
        $allowed['id'] = true;
        foreach ($result as $itemid => $props) {
            $key = (string) $itemid;
            $values[$key] = array_intersect_key($props, $allowed);
        }
        //return $result;
        return $values;
    }

    public function count(array $args)
    {
        if (empty($args['count'])) {
            $args['count'] = true;
        }
        $this->parseQueryArgs($args);
        $objectlist = $this->getObjectList();
        return $this->count;
    }

    public function parseQueryArgs(array $args)
    {
        $allowed = array_flip(['order', 'offset', 'limit', 'filter', 'count', 'access']);
        // $allowed = array_flip(['order', 'offset', 'limit', 'filter', 'count', 'access', 'expand']);
        $args = array_intersect_key($args, $allowed);
        if (!empty($args['order'])) {
            $this->setOrder($args['order']);
        }
        if (!empty($args['limit']) && is_numeric($args['limit'])) {
            $this->setLimit(intval($args['limit']));
        }
        if (!empty($args['offset']) && is_numeric($args['offset'])) {
            $this->setOffset(intval($args['offset']));
        }
        if (!empty($args['filter'])) {
            $filter = $args['filter'];
            if (!is_array($filter)) {
                $filter = [$filter];
            }
            // Clean up arrays by removing false values (= empty, false, null, 0)
            $this->setFilter(array_filter($filter));
        }
        if (!empty($args['count'])) {
            $this->count = $args['count'];
        }
        if (!empty($args['access'])) {
            $this->access = $args['access'];
        }
        // if (!empty($args['expand'])) {
        //     $this->expand = $args['expand'];
        // }
    }

    /**
     * Summary of applyObjectFilter
     * @param DataObjectList $objectlist
     * @return void
     */
    public function applyObjectFilter($objectlist)
    {
        if (empty($this->filter)) {
            return;
        }
        // @todo fix setWhere() and/or dataquery to support other datastores than relational ones
        // See code/modules/dynamicdata/class/ui_handlers/search.php
        $wherestring = '';
        $join = '';
        $mapop = ['eq' => '=', 'ne' => '!=', 'gt' => '>', 'lt' => '<', 'le' => '>=', 'ge' => '<=', 'in' => 'IN'];
        foreach ($this->filter as $where) {
            [$field, $op, $value] = explode(',', $where . ',,');
            if (empty($field) || empty($objectlist->properties[$field]) || empty($op) || empty($mapop[$op])) {
                continue;
            }
            $clause = '';
            if ($op === 'in') {
                // keep only the third variable with the rest of the string, e.g. itemid,in,3,7,11
                [, , $value] = explode(',', $where, 3);
                $value = str_replace("'", "\\'", $value);
                $value = explode(',', $value);
                if (count($value) > 0) {
                    if (is_numeric($value[0])) {
                        $clause = $mapop[$op] . " (" . implode(", ", $value) . ")";
                    } elseif (is_string($value[0])) {
                        $clause = $mapop[$op] . " ('" . implode("', '", $value) . "')";
                    }
                }
            } elseif (is_numeric($value)) {
                $clause = $mapop[$op] . " " . $value;
            } elseif (is_string($value)) {
                $value = str_replace("'", "\\'", $value);
                $clause = $mapop[$op] . " '" . $value . "'";
            }
            if ($clause != '') {
                $objectlist->addWhere($field, $clause, $join);
                // @checkme setWhere() in objects/master.php expects 'field in val1,val2' not 'field in (val1, val2)'
                if ($op === 'in') {
                    $clause = str_replace([", ", "(", ")"], [","], $clause);
                }
                $wherestring .= $join . ' ' . $field . ' ' . trim($clause);
                $join = 'AND';
            }
        }
        if (!empty($wherestring) && is_object($objectlist->datastore) && $objectlist->datastore->getClassName() === 'RelationalDataStore') {
            // @todo support string values in setWhere() 'field in val1,val2'
            $conditions = $objectlist->setWhere($wherestring);
            $objectlist->dataquery->addconditions($conditions);
        }
    }

    public function getCount($objectlist)
    {
        if (!empty($this->count) && !is_integer($this->count)) {
            $this->count = $objectlist->countItems();
        }
        return $this->count;
    }

    public function addPagingParams(array $params = [])
    {
        if (!empty($this->order)) {
            $params['sort'] = [];
            $sorted = array_filter(explode(',', $this->order));
            foreach ($sorted as $sortme) {
                if (substr($sortme, 0, 1) === '-') {
                    $params['sort'][] = substr($sortme, 1) . ' DESC';
                    continue;
                }
                $params['sort'][] = $sortme;
            }
            //$params['sort'] = implode(',', $params['sort']);
        }
        if (!empty($this->limit)) {
            $params['numitems'] = $this->limit;
        }
        if (!empty($this->offset)) {
            $params['startnum'] = $this->offset + 1;
        }
        //if (!empty($this->filter)) {
        //    $params['filter'] = $this->filter;
        //}
        return $params;
    }

    public function getResolver()
    {
        // @checkme use automatic binding of $this here
        $resolver = function ($itemids) {
            return $this->getValues($itemids);
        };
        return $resolver;
    }

    public function setResolver(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    public function setPreLoader(callable $preLoader)
    {
        $this->preLoader = $preLoader;
    }

    public function setPostLoader(callable $postLoader)
    {
        $this->postLoader = $postLoader;
    }
}

class DataObjectItemLoader extends DataObjectLoader
{
    public function add($value)
    {
        //assert(is_int($value));
        $this->addItem((int)$value);
    }

    public function get($value)
    {
        //assert(is_int($value));
        if (!empty($this->todo)) {
            $this->load();
        }
        // @checkme don't slice array based on limit and offset here?
        return $this->getItem((int)$value);
    }
}

class DataObjectListLoader extends DataObjectLoader
{
    public function add($values)
    {
        //assert(is_array($values));
        $this->addList($values);
    }

    public function get($values)
    {
        //assert(is_array($values));
        if (!empty($this->todo)) {
            $this->load();
        }
        return $this->getList($values);
    }
}

class DataObjectDummyLoader extends DataObjectLoader
{
    public function add($values)
    {
        // pass
    }

    public function get($values)
    {
        return $values;
    }

    public function getValues(array $itemids)
    {
        return [];
    }
}

class LinkObjectItemLoader extends DataObjectItemLoader
{
    public $linkname = '';
    public $caller_id = '';
    public $called_id = '';
    public $targetLoader = null;

    public function __construct(string $linkname = 'sample', string $caller_id = '', string $called_id = '', ?callable $resolver = null)
    {
        $this->linkname = $linkname;
        $this->caller_id = $caller_id;
        $this->called_id = $called_id;
        $this->targetLoader = null;
        parent::__construct($linkname, [$caller_id, $called_id], $resolver);
    }

    public function setTarget(string $objectname = 'sample', array $fieldlist = ['id', 'name'])
    {
        // @todo if objectname is the same as linkname here, we could retrieve all fields at once below
        // @checkme we could use a DataObjectListLoader and pass all the values to it, but that's less efficient
        //$this->targetLoader = new DataObjectListLoader($objectname, $fieldlist, $resolver);
        //$this->targetLoader = new DataObjectItemLoader($objectname, $fieldlist);
        $this->targetLoader = DataObjectLoader::getItemLoader($objectname, $fieldlist);
    }

    public function getTarget()
    {
        return $this->targetLoader;
    }

    public function getValues(array $itemids)
    {
        xarLog::message("LinkObjectItemLoader::getValues: get links for " . count($itemids) . " items from " . $this->linkname, xarLog::LEVEL_INFO);
        $fieldlist = [$this->caller_id, $this->called_id];
        $params = ['name' => $this->linkname, 'fieldlist' => $fieldlist];
        //$params = array('name' => $object, 'fieldlist' => $fieldlist, 'itemids' => $values);
        $this->objectlist = DataObjectMaster::getObjectList($params);
        // @checkme relational objects filter fieldlist param based on status in objectlist constructor?
        // @todo make this query work for relational datastores: select where caller_id in $values
        //$params = array('where' => [$caller_id . ' in ' . implode(',', $values)]);
        //$result = $this->objectlist->getItems($params);
        $this->objectlist->addWhere($this->caller_id, 'IN (' . implode(',', $itemids) . ')');
        if (is_object($this->objectlist->datastore) && $this->objectlist->datastore->getClassName() === 'RelationalDataStore') {
            $wherestring = $this->caller_id . ' IN ' . implode(',', $itemids);
            // @todo support string values in setWhere() 'field in val1,val2'
            $conditions = $this->objectlist->setWhere($wherestring);
            $this->objectlist->dataquery->addconditions($conditions);
        }
        $result = $this->objectlist->getItems();
        // return array("$caller_id" => list of $called_ids)
        $values = [];
        foreach ($result as $itemid => $props) {
            $key = (string) $props[$this->caller_id];
            if (!array_key_exists($key, $values)) {
                $values[$key] = [];
            }
            $values[$key][] = $props[$this->called_id];
        }
        xarLog::message("LinkObjectItemLoader::getValues: got " . count($values) . " values from " . $this->linkname, xarLog::LEVEL_INFO);
        return $values;
    }

    public function getObjectList(array $params = [])
    {
        if (empty($params)) {
            $fieldlist = [$this->caller_id, $this->called_id];
            $params = ['name' => $this->linkname, 'fieldlist' => $fieldlist];
            //$params = array('name' => $object, 'fieldlist' => $fieldlist, 'itemids' => $values);
        }
        $this->objectlist = DataObjectMaster::getObjectList($params);
        // @checkme relational objects filter fieldlist param based on status in objectlist constructor?
        return $this->objectlist;
    }

    public function mergeFieldlist(array $fieldlist)
    {
        if (!empty($fieldlist) && $this->checkFieldlist && $this->targetLoader) {
            $this->targetLoader->mergeFieldlist($fieldlist);
            $this->checkFieldlist = false;
        }
    }

    public function postLoad()
    {
        if (empty($this->targetLoader)) {
            parent::postLoad();
            return;
        }
        // @checkme fieldlist was updated, pass along to target loader
        //if (!$this->checkFieldlist) {
        //    $this->targetLoader->mergeFieldlist($this->fieldlist);
        //}
        // @checkme we could use a DataObjectListLoader and pass all the values to it, but that's less efficient
        //foreach ($this->cache as $key => $values) {
        //    $this->targetLoader->add($values);
        //}
        // @checkme we need to find the unique itemids across all values here - this is probably more memory-efficient
        $distinct = [];
        foreach ($this->cache as $key => $values) {
            // @checkme slice array based on limit and offset here?
            if (!empty($this->limit) || !empty($this->offset)) {
                $values = array_slice($values, $this->offset, $this->limit);
            }
            $distinct = array_unique(array_merge($distinct, $values));
        }
        if (empty($distinct)) {
            return true;
        }
        $this->targetLoader->addList($distinct);
        $this->targetLoader->load();
        parent::postLoad();
    }

    public function getItem(int $value)
    {
        if (!empty($value) &&
            array_key_exists("$value", $this->cache)) {
            //return $this->cache["$value"];
            // @checkme slice array based on limit and offset here?
            $oldvalues = $this->cache["$value"];
            if (!empty($this->limit) || !empty($this->offset)) {
                $oldvalues = array_slice($oldvalues, $this->offset, $this->limit);
            }
            if (empty($this->targetLoader)) {
                return $oldvalues;
            }
            $newvalues = [];
            foreach ($oldvalues as $itemid) {
                $id = (string) $itemid;
                $newvalues[$id] = $this->targetLoader->get($itemid);
            }
            return $newvalues;
        }
    }

    /**
     * Allow setting the cache values for showInput() in preview mode
     */
    public function set(int $itemid, array $values)
    {
        if (!empty($itemid)) {
            $this->cache["$itemid"] = $values;
            if (!empty($values) && !empty($this->targetLoader)) {
                $this->targetLoader->addList($values);
                $this->targetLoader->load();
            }
        }
    }

    /**
     * Save the new values in the Link Object for updateValue()
     */
    public function save(int $itemid, array $values)
    {
        if (empty($itemid)) {
            return;
        }
        if (!empty($this->targetLoader) && $this->targetLoader->objectname === $this->linkname) {
            throw new Exception('No saving links to complete child object ' . $this->linkname);
        }
        $params = ['name' => $this->linkname];
        $objectlist = DataObjectMaster::getObjectList($params);
        $objectlist->addWhere($this->caller_id, '= ' . $itemid);
        if (is_object($objectlist->datastore) && $objectlist->datastore->getClassName() === 'RelationalDataStore') {
            $wherestring = $this->caller_id . ' = ' . $itemid;
            $conditions = $objectlist->setWhere($wherestring);
            $objectlist->dataquery->addconditions($conditions);
            // @todo make sure we don't delete the wrong items here
            throw new Exception('TODO: No saving links to relational object ' . $this->linkname);
        }
        $result = $objectlist->getItems();
        $oldlinks = [];
        foreach ($result as $linkid => $props) {
            $oldlinks[intval($props[$this->called_id])] = $linkid;
        }
        $newvalues = array_diff($values, array_keys($oldlinks));
        $delvalues = array_diff(array_keys($oldlinks), $values);
        if (empty($newvalues) && empty($delvalues)) {
            return;
        }
        // xarLog::message("LinkObjectItemLoader::save: old links " . implode(', ', $oldlinks), xarLog::LEVEL_INFO);
        // xarLog::message("LinkObjectItemLoader::save: new values " . implode(', ', $newvalues), xarLog::LEVEL_INFO);
        // xarLog::message("LinkObjectItemLoader::save: del values " . implode(', ', $delvalues), xarLog::LEVEL_INFO);
        $objectref = DataObjectMaster::getObject($params);
        foreach ($delvalues as $called_id) {
            $objectref->deleteItem(['itemid' => $oldlinks[$called_id]]);
        }
        foreach ($newvalues as $called_id) {
            $objectref->createItem([$this->caller_id => $itemid, $this->called_id => $called_id]);
        }
    }
}

class LinkObjectListLoader extends DataObjectListLoader
{
}
