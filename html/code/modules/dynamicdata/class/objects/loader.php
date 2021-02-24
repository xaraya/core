<?php
/**
 * The DataObject Loader delays loading values from the database until they are requested.
 * It was inspired by how GraphQL-PHP tackles the N+1 problem, but without proxy, callable or promises (sync or async).
 * Right now this would be the equivalent of lazy loading in batch for showOutput() in object lists :-)
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
    public $fieldlist = array();
    public $todo = array();
    public $cache = array();
    public $resolver = null;
    public $preLoader = null;
    public $postLoader = null;
    public $checkFieldlist = true;

    public function __construct(string $objectname = 'sample', array $fieldlist = ['id', 'name'], ?callable $resolver = null)
    {
        $this->objectname = $objectname;
        $this->fieldlist = $fieldlist;
        $this->todo = array();
        $this->cache = array();
        $this->resolver = $resolver;
        $this->preLoader = null;
        $this->postLoader = null;
        $this->checkFieldlist = true;
    }

    public function add($value)
    {
        if (is_array($value)) {
            $this->addList($value);
        } else {
            $this->addItem($value);
        }
    }

    public function addItem(int $value)
    {
        if (!empty($value) &&
            !in_array($value, $this->todo) &&
            !array_key_exists("$value", $this->cache)) {
            $this->todo[] = $value;
        }
    }

    public function addList(array $values)
    {
        foreach ($values as $value) {
            $this->addItem($value);
        }
    }

    public function get($value)
    {
        if (!empty($this->todo)) {
            $this->load();
        }
        if (is_array($value)) {
            return $this->getList($value);
        } else {
            return $this->getItem($value);
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
        $items = array();
        foreach ($values as $value) {
            $key = (string) $value;
            $items[$key] = $this->getItem($value);
        }
        return $items;
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
        $this->todo = array();
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
        $params = array('name' => $this->objectname, 'fieldlist' => $this->fieldlist);
        //$params = array('name' => $this->objectname, 'fieldlist' => $this->fieldlist, 'itemids' => $itemids);
        $objectlist = DataObjectMaster::getObjectList($params);
        $params = array('itemids' => $itemids);
        $result = $objectlist->getItems($params);
        // return array("$itemid" => assoc array of $fields)
        // @checkme variabletable returns all fields at the moment - filter here for now
        //return $result;
        $values = array();
        // key white-list filter - https://www.php.net/manual/en/function.array-intersect-key.php
        $allowed = array_flip($this->fieldlist);
        //$addPrimary = false;
        //if (!empty($objectlist->primary) && !in_array($objectlist->primary, $this->fieldlist)) {
        //    $allowed[$objectlist->primary] = true;
        //    $addPrimary = true;
        //}
        foreach ($result as $itemid => $props) {
            //if ($addPrimary) {
            //    $props[$objectlist->primary] = $itemid;
            //}
            $key = (string) $itemid;
            $values[$key] = array_intersect_key($props, $allowed);
        }
        /**
        // return array("$itemid" => single $field value)
        $first = reset($result);
        $field = array_pop($fieldlist);
        if (!array_key_exists($field, $first)) {
            // @checkme pick the first key available here?
            $fieldlist = array_keys($first);
            $field = array_shift($fieldlist);
        }
        $values = array();
        foreach ($result as $itemid => $props) {
            $key = (string) $itemid;
            $values[$key] = $props[$field];
        }
         */
        return $values;
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
    public function add(int $value)
    {
        $this->addItem($value);
    }

    public function get(int $value)
    {
        if (!empty($this->todo)) {
            $this->load();
        }
        return $this->getItem($value);
    }
}

class DataObjectListLoader extends DataObjectLoader
{
    public function add(array $values)
    {
        $this->addList($values);
    }

    public function get(array $values)
    {
        if (!empty($this->todo)) {
            $this->load();
        }
        return $this->getList($values);
    }
}

class LinkObjectItemLoader extends DataObjectItemLoader
{
    public $linkname = '';
    public $caller_id = '';
    public $called_id = '';
    public $targetLoader = null;

    public function __construct(string $linkname = 'sample', string $caller_id, string $called_id, ?callable $resolver = null)
    {
        $this->linkname = $linkname;
        $this->caller_id = $caller_id;
        $this->called_id = $called_id;
        $this->targetLoader = null;
        parent::__construct($linkname, [$caller_id, $called_id], $resolver);
    }

    public function setTarget(string $objectname = 'sample', array $fieldlist = ['id', 'name'], ?callable $resolver = null)
    {
        // @checkme we could use a DataObjectListLoader and pass all the values to it, but that's less efficient
        //$this->targetLoader = new DataObjectListLoader($objectname, $fieldlist, $resolver);
        $this->targetLoader = new DataObjectItemLoader($objectname, $fieldlist, $resolver);
    }

    public function getTarget()
    {
        return $this->targetLoader;
    }

    public function getValues(array $itemids)
    {
        $fieldlist = array($this->caller_id, $this->called_id);
        $params = array('name' => $this->linkname, 'fieldlist' => $fieldlist);
        //$params = array('name' => $object, 'fieldlist' => $fieldlist, 'itemids' => $values);
        $objectlist = DataObjectMaster::getObjectList($params);
        // @todo make this query work for relational datastores: select where caller_id in $values
        //$params = array('where' => [$caller_id . ' in ' . implode(',', $values)]);
        //$result = $objectlist->getItems($params);
        //print_r('Query ' . $linkname . ' with ' . $caller_id . ' IN (' . implode(',', $values) . ')');
        $objectlist->addWhere($this->caller_id, 'IN (' . implode(',', $itemids) . ')');
        $result = $objectlist->getItems();
        $values = array();
        foreach ($result as $itemid => $props) {
            $key = (string) $props[$this->caller_id];
            if (!array_key_exists($key, $values)) {
                $values[$key] = array();
            }
            $values[$key][] = $props[$this->called_id];
        }
        return $values;
    }

    public function postLoad()
    {
        if (empty($this->targetLoader)) {
            parent::postLoad();
            return;
        }
        // @checkme fieldlist was updated, pass along to target loader
        if (!$this->checkFieldlist) {
            $this->targetLoader->mergeFieldlist($this->fieldlist);
        }
        // @checkme we could use a DataObjectListLoader and pass all the values to it, but that's less efficient
        //foreach ($this->cache as $key => $values) {
        //    $this->targetLoader->add($values);
        //}
        // @checkme we need to find the unique itemids across all values here - this is probably more memory-efficient
        $distinct = array();
        foreach ($this->cache as $key => $values) {
            $distinct = array_unique(array_merge($distinct, $values));
        }
        if (empty($distinct)) {
            return true;
        }
        $items = $this->targetLoader->getValues($distinct);
        foreach (array_keys($this->cache) as $key) {
            $oldvalues = $this->cache[$key];
            $newvalues = array();
            foreach ($oldvalues as $itemid) {
                $id = (string) $itemid;
                if (array_key_exists($id, $items)) {
                    $newvalues[$id] = $items[$id];
                } else {
                    $newvalues[$id] = $id;
                }
            }
            $this->cache[$key] = $newvalues;
        }
        parent::postLoad();
    }
}

class LinkObjectListLoader extends DataObjectListLoader
{
}
