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
    public $checkFields = true;

    public function __construct(string $objectname = 'modules', array $fieldlist = ['name'], ?callable $resolver = null, ?callable $preLoader = null, ?callable $postLoader = null)
    {
        $this->objectname = $objectname;
        $this->fieldlist = $fieldlist;
        $this->resolver = $resolver;
        $this->preLoader = $preLoader;
        $this->postLoader = $postLoader;
        $this->todo = array();
        $this->cache = array();
        $this->checkFields = true;
    }

    public function add(int $value)
    {
        if (isset($value) &&
            !in_array($value, $this->todo) &&
            !array_key_exists("$value", $this->cache)) {
            $this->todo[] = $value;
        }
    }

    public function get(int $value)
    {
        if (!empty($this->todo)) {
            $this->load();
        }
        if (isset($value) &&
            array_key_exists("$value", $this->cache)) {
            return $this->cache["$value"];
        }
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
        foreach ($result as $itemid => $props) {
            $key = (string) $itemid;
            $values[$key] = array_intersect_key($props, $allowed);
        }
        return $values;
    }

    public function setFieldlist(array $fieldlist)
    {
        $this->fieldlist = $fieldlist;
    }

    public function mergeFieldlist(array $fieldlist)
    {
        if (!empty($fieldlist) && $this->checkFields) {
            $this->fieldlist = array_unique(array_merge($this->fieldlist, $fieldlist));
            $this->checkFields = false;
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
