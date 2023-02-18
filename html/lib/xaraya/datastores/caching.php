<?php
/**
 * Data Store is supported by cacheStorage (dummy = 1 request only or apcu = somewhat persistent by default)
 *
 * Can be used by virtual or actual dataobjects, but limited to non-SQL operations based on itemid(s)
 *
 * @package core\datastores
 * @subpackage datastores
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
**/

/**
 * Class for cache datastore
 */
sys::import('modules.dynamicdata.class.datastores.base');

class CachingDataStore extends BasicDataStore
{
    protected $cacheStorage;

    public function getItem(array $args = [])
    {
        // Get the itemid from the params or from the object definition
        $itemid = $args['itemid'] ?? $this->object->itemid;
        if (empty($itemid)) {
            throw new Exception(xarML('Cannot get itemid 0'));
        }
        $value = $this->getCacheStorage()->getCached($itemid);
        echo "Getting item $itemid: $value";
        $item = unserialize($value);
        if (!empty($this->object->primary) && $this->object->primary !== 'itemid') {
            $item[$this->object->primary] = $itemid;
        }
        $fieldlist = $this->object->getFieldList();
        foreach ($fieldlist as $field) {
            if (!isset($item[$field])) {
                continue;
            }
            $this->object->properties[$field]->setValue($item[$field]);
        }
        return $itemid;
    }

    public function getItems(array $args = [])
    {
        if (!empty($args['itemids'])) {
            $itemids = $args['itemids'];
        } elseif (isset($this->_itemids)) {
            $itemids = $this->_itemids;
        } else {
            $itemids = [];
        }
        $fieldlist = $this->object->getFieldList();
        foreach ($itemids as $itemid) {
            $value = $this->getCacheStorage()->getCached($itemid);
            $item = unserialize($value);
            if (!empty($this->object->primary) && $this->object->primary !== 'itemid') {
                $item[$this->object->primary] = $itemid;
            }
            foreach ($fieldlist as $field) {
                if (!isset($item[$field])) {
                    continue;
                }
                $this->object->properties[$field]->setItemValue($itemid, $item[$field]);
            }
        }
        return $itemids;
    }

    public function countItems(array $args = [])
    {
        return count($this->_itemids ?? []);
    }

    public function createItem(array $args = [])
    {
        // Get the itemid from the params or from the object definition
        $itemid = $args['itemid'] ?? $this->object->itemid;
        if (empty($itemid)) {
            throw new Exception(xarML('Cannot create itemid 0'));
        }
        $item = array_merge(['itemid' => $itemid], $args);
        if (!empty($this->object->primary) && $this->object->primary !== 'itemid') {
            $item[$this->object->primary] = $itemid;
        }
        $fieldlist = $this->object->getFieldList();
        foreach ($fieldlist as $field) {
            $item[$field] = $this->object->properties[$field]->getValue();
        }
        $value = serialize($item);
        echo "Creating item $itemid: $value";
        $this->getCacheStorage()->setCached($itemid, $value);
        return $itemid;
    }

    public function updateItem(array $args = [])
    {
        // Get the itemid from the params or from the object definition
        $itemid = $args['itemid'] ?? $this->object->itemid;
        if (empty($itemid)) {
            throw new Exception(xarML('Cannot update itemid 0'));
        }
        $item = $this->getItem($itemid);
        $item = array_merge($item, $args);
        if (!empty($this->object->primary) && $this->object->primary !== 'itemid') {
            $item[$this->object->primary] = $itemid;
        }
        $fieldlist = $this->object->getFieldList();
        foreach ($fieldlist as $field) {
            $item[$field] = $this->object->properties[$field]->getValue();
        }
        $value = serialize($item);
        $this->getCacheStorage()->setCached($itemid, $value);
        return $itemid;
    }

    public function deleteItem(array $args = [])
    {
        // Get the itemid from the params or from the object definition
        $itemid = $args['itemid'] ?? $this->object->itemid;
        if (empty($itemid)) {
            throw new Exception(xarML('Cannot delete itemid 0'));
        }
        $this->getCacheStorage()->delCached($itemid);
        return $itemid;
    }

    public function getCacheStorage()
    {
        if (!empty($this->cacheStorage)) {
            return $this->cacheStorage;
        }
        // Note: we use dummy or apcu by default here - see VirtualObjectDescriptor
        $this->cacheStorage = xarCache::getStorage([
            'storage'   => $this->object->cachestorage ?? 'dummy',
            'type'      => 'datastore',
            //'provider'  => $provider,
            // we (won't) store cache files under this
            //'cachedir'  => self::$cacheDir,
            //'expire'    => self::$cacheTime,
            //'sizelimit' => self::$cacheSizeLimit,
            //'logfile'   => $logfile,
        ]);
        // we use the object name as namespace here
        $this->cacheStorage->setNamespace($this->object->name . '-');
        return $this->cacheStorage;
    }
}
