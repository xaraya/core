<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */

sys::import('modules.dynamicdata.class.objects.factory');

/**
 * DataObjectLinks class
 */
class DataObjectLinks extends xarObject
{
    public static $linktypes = [
        'children'   => 'is parent of (one-to-many)',
        'parents'    => 'is child of (many-to-one)',
        'linkedto'   => 'is linked to (one-to-one)',
        'linkedfrom' => 'is linked from (one-to-one)',
        'extensions' => 'is extended by (one-to-many)',
        'extended'   => 'is extended from (many-to-one)',
    ];
    public static $reverselinktypes = [
        'parents'    => 'children',
        'children'   => 'parents',
        'linkedfrom' => 'linkedto',
        'linkedto'   => 'linkedfrom',
        'extended'   => 'extensions',
        'extensions' => 'extended',
    ];
    public static $directions = [
        'bi'   => 'two-way',
        'uni'  => 'one-way',
        'info' => 'info',
    ];

    /**
     * Initialize DataObjectLinks by importing the necessary xml files if necessary
     * @return DataObjectList|void
     */
    public static function initLinks()
    {
        $linklist = null;
        try {
            $linklist = DataObjectFactory::getObjectList(['name' => 'dynamic_object_links']);
        } catch (Exception $e) {
            if (empty($linklist) || empty($linklist->objectid)) {
                $def_file = sys::code() . 'modules/dynamicdata/xardata/dynamic_object_links-def.xml';
                $dat_file = sys::code() . 'modules/dynamicdata/xardata/dynamic_object_links-dat.xml';
                if (file_exists($def_file)) {
                    $objectid = xarMod::apiFunc(
                        'dynamicdata',
                        'util',
                        'import',
                        ['file' => $def_file]
                    );
                    if (empty($objectid)) {
                        return;
                    }
                }
                if (file_exists($dat_file)) {
                    $objectid = xarMod::apiFunc(
                        'dynamicdata',
                        'util',
                        'import',
                        ['file' => $dat_file]
                    );
                    if (empty($objectid)) {
                        return;
                    }
                }
                $linklist = DataObjectFactory::getObjectList(['name' => 'dynamic_object_links']);
            }
        }
        return $linklist;
    }

    /**
     * Get links for an object
     *
     * @param $source the object we want to get the links for (object, objectlist, objectname or objectid)
     * @param $linktype the type of links we're looking for (default, parents, children, linkedto, linkedfrom, info, all)
     */
    public static function getLinks($source = '', $linktype = '', $itemid = null)
    {
        $linklist = self::initLinks();
        if (empty($linklist) || empty($linklist->objectid)) {
            return [];
        }

        $source = self::getName($source);

        $where = [];

        // CHECKME: do we support getting the links for all objects here ?
        if (!empty($source)) {
            $where[] = "source eq '$source'";
        }

        // check what link_type we need to filter on (except 'info' and 'all')
        if (!empty($linktype) && !empty(self::$linktypes[$linktype])) {
            $where[] = "link_type eq '" . $linktype . "'";
        }

        // we'll skip the 'info' links here, unless explicitly asked for by 'info' or 'all'
        if ($linktype == 'info') {
            $where[] = "direction eq 'info'";
        } elseif ($linktype != 'all') {
            $where[] = "direction ne 'info'";
        }

        // get the links for this source, link_type and direction
        if (!empty($where)) {
            $items = $linklist->getItems(['where' => implode(' and ', $where)]);
        } else {
            $items = $linklist->getItems();
        }

        // return as source => links array
        $links = [];
        foreach ($items as $link) {
            if (empty($links[$link['source']])) {
                $links[$link['source']] = [];
            }
            $links[$link['source']][] = $link;
        }
        return $links;
    }

    /**
     * Add a link between a source object and a target object
     *
     * @param $from_object the object we want to add a link from (object, objectlist, objectname or objectid)
     * @param $from_propname the property we want to add a link from
     * @param $to_object the object we want to add a link to (object, objectlist, objectname or objectid)
     * @param $to_propname the property we want to add a link to
     * @param $link_type the type of link we want to add
     * @param $direction the direction of the link we want to add (bi, uni or info)
     * @param $extra additional constraints for this link
     * @param $add_reverse if we want to add a reverse link from target to source too (default is always true)
     */
    public static function addLink($from_object, $from_propname, $to_object, $to_propname, $link_type, $direction, $extra = '', $add_reverse = true)
    {
        $from_object = self::getName($from_object);
        $to_object = self::getName($to_object);
        if (empty($from_object) || empty($to_object)) {
            return;
        }

        $link = ['source'    => $from_object,
                      'from_prop' => $from_propname,
                      'target'    => $to_object,
                      'to_prop'   => $to_propname,
                      'link_type' => $link_type,
                      'direction' => $direction,
                      'extra'     => $extra];

        // get the list of all existing links
        $linklist = self::initLinks();
        if (empty($linklist) || empty($linklist->objectid)) {
            return;
        }
        $checklinks = $linklist->getItems();
        if (empty($checklinks)) {
            $checklinks = [];
        }

        // make sure the link doesn't exist yet
        $link_id = 0;
        foreach ($checklinks as $checklink) {
            if ($link['source'] == $checklink['source'] &&
                $link['from_prop'] == $checklink['from_prop'] &&
                $link['target'] == $checklink['target'] &&
                $link['to_prop'] == $checklink['to_prop'] &&
                $link['link_type'] == $checklink['link_type']) {
                $link_id = $checklink['id'];
                break;
            }
        }

        // create the link
        if (empty($link_id)) {
            $linkobject = DataObjectFactory::getObject(['name' => 'dynamic_object_links']);
            if (empty($linkobject) || empty($linkobject->objectid)) {
                return;
            }

            $link_id = $linkobject->createItem($link);
            if (empty($link_id)) {
                return;
            }
        }

        // see if we need to create a reverse link too
        if (empty($add_reverse) || empty(self::$reverselinktypes[$link_type])) {
            // nothing more to add
            return $link_id;
        }

        // determine the link_type for the reverse link
        $reversetype = self::$reverselinktypes[$link_type];

        // for uni-directional links from source to target, we'll store an 'info' link back from target to source, just so we know it exists
        if ($direction == 'uni') {
            $reversedir = 'info';
        } elseif ($direction == 'info') {
            $reversedir = 'uni';
        } else {
            $reversedir = 'bi';
        }

        $link = ['source'    => $to_object,
                      'from_prop' => $to_propname,
                      'target'    => $from_object,
                      'to_prop'   => $from_propname,
                      'link_type' => $reversetype,
                      'direction' => $reversedir,
                      // CHECKME: probably not the right syntax in reverse !
                      'extra'     => $extra];

        // make sure the reverse link doesn't exist yet
        $link_id = 0;
        foreach ($checklinks as $checklink) {
            if ($link['source'] == $checklink['source'] &&
                $link['from_prop'] == $checklink['from_prop'] &&
                $link['target'] == $checklink['target'] &&
                $link['to_prop'] == $checklink['to_prop'] &&
                $link['link_type'] == $checklink['link_type']) {
                $link_id = $checklink['id'];
                break;
            }
        }

        if (!empty($link_id)) {
            // nothing more to add
            return $link_id;
        }

        // create the reverse link
        if (empty($linkobject)) {
            $linkobject = DataObjectFactory::getObject(['name' => 'dynamic_object_links']);
            if (empty($linkobject) || empty($linkobject->objectid)) {
                return;
            }
        }

        $link_id = $linkobject->createItem($link);
        return $link_id;
    }

    /**
     * Remove a link between a source object and a target object
     */
    public static function removeLink($link_id, $remove_reverse = true)
    {
        $linkobject = DataObjectFactory::getObject(['name' => 'dynamic_object_links']);
        if (empty($linkobject) || empty($linkobject->objectid)) {
            return;
        }

        $link_id = $linkobject->getItem(['itemid' => $link_id]);
        if (empty($link_id)) {
            return;
        }

        $linkfields = $linkobject->getFieldValues();

        $link_id = $linkobject->deleteItem();
        if (empty($remove_reverse)) {
            // nothing more to remove
            return $link_id;
        }

        // get all links from the target (= including 'info')
        $links = self::getLinks($linkfields['target'], 'all');
        if (empty($links[$linkfields['target']])) {
            return $link_id;
        }

        // determine the link_type for the reverse link
        $reversetype = self::$reverselinktypes[$linkfields['link_type']];

        foreach ($links[$linkfields['target']] as $link) {
            // find the corresponding link from target to source
            if ($link['target'] == $linkfields['source'] &&
                $link['to_prop'] == $linkfields['from_prop'] &&
                $link['from_prop'] == $linkfields['to_prop'] &&
                $link['link_type'] == $reversetype) {

                $link_id = $linkobject->getItem(['itemid' => $link['id']]);
                if (empty($link_id) || $link_id != $link['id']) {
                    continue;
                }
                $link_id = $linkobject->deleteItem();
            }
        }
        return $link_id;
    }

    /**
     * Get the name of object arguments (object, objectlist, objectid or objectname)
     */
    public static function getName($object)
    {
        if (empty($object)) {
            return;
        } elseif (is_numeric($object)) {
            $info = DataObjectFactory::getObjectInfo(['objectid' => $object]);
            return $info['name'];
        } elseif (is_string($object)) {
            return $object;
        } elseif (is_object($object) && !empty($object->name)) {
            return $object->name;
        }
    }

    /**
     * Get linked objects for a DataObject or DataObjectList (work in progress)
     *
     * @param DataObject|DataObjectList $object the object we want to get the links for (object or objectlist)
     * @param string $linktype the type of links we're looking for (default, parents, children, linkedto, linkedfrom, info, all)
     * @param ?int $itemid (optional) for a particular itemid in ObjectList ?
     */
    public static function getLinkedObjects($object, $linktype = '', $itemid = null)
    {
        // we'll skip the 'info' here, unless explicitly asked for 'all'
        $links = self::getLinks($object, $linktype, $itemid);
        if (empty($links[$object->name])) {
            return [];
        }

        // CHECKME: review where we place the linked objects
        $object->links = [
            'parents'    => [],
            'children'   => [],
            'linkedfrom' => [],
            'linkedto'   => [],
            'info'       => [],
        ];

        //$linked = array();
        foreach ($links[$object->name] as $link) {
            // skip links from unknown properties
            if (empty($object->properties[$link['from_prop']])) {
                continue;
            }

            // get an objectlist for the target
            $linkedlist = DataObjectFactory::getObjectList(['name' => $link['target']]);

            // skip links to unknown objects or properties
            if (empty($linkedlist->objectid) || empty($linkedlist->properties[$link['to_prop']])) {
                continue;
            }

            // initialize the linked list
            if (empty($object->properties[$link['from_prop']]->linked)) {
                $object->properties[$link['from_prop']]->linked = [];
            }

            if (!empty($object->itemid)) {
                // get item(s) ?
                $where = [];
                // get original role id
                //if ($object->properties[$link['from_prop']]->type == 7) {
                //                $value = $object->properties[$link['from_prop']]->value;
                //} else {
                $value = $object->properties[$link['from_prop']]->getValue();
                //}
                if (isset($value)) {
                    if (is_numeric($value)) {
                        $where[] = $link['to_prop'] . ' = ' . $value;
                    } elseif (is_string($value)) {
                        $where[] = $link['to_prop'] . " = '" . $value . "'";
                    } elseif (is_array($value)) {
                        $where[] = $link['to_prop'] . " IN ('" . implode("', '", $value) . "')";
                    } else {
                        // no idea what to do with this ;-)
                    }
                }
                if (!empty($link['extra'])) {
                    $where[] = $link['extra'];
                }
                if (!empty($where)) {

                    $linkedlist->getItems(['where' => implode(' and ', $where)]);
                    /* CHECKME: turn linkedto, linkedfrom and parents into a single object ?
                                        if (!empty($linkedlist->itemids) && count($linkedlist->itemids) == 1) {
                                            $itemid = $linkedlist->itemids[0];
                                            $item = $linkedlist->items[$itemid];
                                            // get a single object for the target
                                            $linkedlist = DataObjectFactory::getObject(array('name' => $link['target']));
                                            $linkedlist->itemid = $itemid;
                                            $linkedlist->setFieldValues($item);
                                        }
                    */
                }

            } elseif (!empty($object->itemids)) {
                // TODO: get item(s) ?
            }

            $link['list'] = $linkedlist;

            // CHECKME: review where we place the linked objects
            $object->links[$link['link_type']][] = $link;
            $object->properties[$link['from_prop']]->linked[] = & $link;

            //$linked[] = $link;
        }
        //return $linked;
        //return $object->links;
    }

    /**
     * Count linked object items for a DataObject or DataObjectList (work in progress)
     *
     * @param DataObject|DataObjectList $object the object we want to count the linked object items for (object or objectlist)
     * @param string $linktype the type of links we're looking for (default, parents, children, linkedto, linkedfrom, info, all)
     * @param ?int $itemid (optional) for a particular itemid in ObjectList ?
     */
    public static function countLinkedItems($object, $linktype = '', $itemid = null)
    {
        // we'll skip the 'info' here, unless explicitly asked for 'all'
        $links = self::getLinks($object, $linktype, $itemid);
        if (empty($links[$object->name])) {
            return [];
        }

        // CHECKME: use this only to count children here, or also for the others (= 0 or 1) ?

        // CHECKME: review where we place the linked objects
        $object->links = [
            'parents'    => [],
            'children'   => [],
            'linkedfrom' => [],
            'linkedto'   => [],
            'info'       => [],
        ];

        //$linked = array();
        foreach ($links[$object->name] as $link) {
            // skip links from unknown properties
            if (empty($object->properties[$link['from_prop']])) {
                continue;
            }

            // get an objectlist for the target
            $linkedlist = DataObjectFactory::getObjectList(['name' => $link['target']]);
            // skip links to unknown objects or properties
            if (empty($linkedlist->objectid) || empty($linkedlist->properties[$link['to_prop']])) {
                continue;
            }

            // initialize the linked list
            if (empty($object->properties[$link['from_prop']]->linked)) {
                $object->properties[$link['from_prop']]->linked = [];
            }

            $linkedcount = [];
            if (!empty($object->itemid)) {
                // get item(s) ?
                $where = [];
                // get original role id
                //if ($object->properties[$link['from_prop']]->type == 7) {
                //                $value = $object->properties[$link['from_prop']]->value;
                //} else {
                $value = $object->properties[$link['from_prop']]->getValue();
                //}
                if (isset($value)) {
                    if (is_numeric($value)) {
                        $where[] = $link['to_prop'] . ' = ' . $value;
                    } elseif (is_string($value)) {
                        $where[] = $link['to_prop'] . " = '" . $value . "'";
                    } elseif (is_array($value)) {
                        $where[] = $link['to_prop'] . " IN ('" . implode("', '", $value) . "')";
                    } else {
                        // no idea what to do with this ;-)
                    }
                }
                if (!empty($link['extra'])) {
                    $where[] = $link['extra'];
                }
                if (!empty($where)) {
                    $linkedcount = $linkedlist->countItems(['where' => implode(' and ', $where)]);
                }

            } elseif (!empty($object->itemids)) {
                $where = [];
                if (!empty($object->where)) {
                    $values = [];
                    $value2items = [];
                    foreach ($object->itemids as $itemid) {
                        $value = $object->properties[$link['from_prop']]->getItemValue($itemid);
                        if (isset($value)) {
                            array_push($values, $value);
                            if (!isset($value2items[$value])) {
                                $value2items[$value] = [];
                            }
                            array_push($value2items[$value], $itemid);
                        }
                    }
                    if (!empty($values)) {
                        // filter by to_prop values
                        $where[] = $link['to_prop'] . " IN ('" . implode("', '", $values) . "')";
                    }
                } else {
                    // map from_prop values to itemids
                    $value2items = [];
                    foreach ($object->itemids as $itemid) {
                        $value = $object->properties[$link['from_prop']]->getItemValue($itemid);
                        if (isset($value)) {
                            if (!isset($value2items[$value])) {
                                $value2items[$value] = [];
                            }
                            array_push($value2items[$value], $itemid);
                        }
                    }
                }
                if (!empty($linkedlist->primary)) {
                    // group by $link['to_prop']
                    $itemcounts = $linkedlist->getItems(['fieldlist' => ['COUNT(' . $linkedlist->primary . ')', $link['to_prop']],
                                                              'groupby' => $link['to_prop'],
                                                              'where' => implode(' and ', $where)]);
                    foreach ($itemcounts as $item) {
                        if (isset($item[$link['to_prop']])) {
                            $value = $item[$link['to_prop']];
                            if (empty($value2items[$value])) {
                                continue;
                            }
                            // map to_prop value to itemids
                            foreach ($value2items[$value] as $itemid) {
                                $linkedcount[$itemid] = $item[$linkedlist->primary];
                            }
                        }
                    }
                } else {
                    // now what ?
                }
            }

            $link['label'] = $linkedlist->label;
            $link['count'] = $linkedcount;

            // CHECKME: review where we place the linked objects
            $object->links[$link['link_type']][] = $link;
            $object->properties[$link['from_prop']]->linked[] = & $link;

            //$linked[] = $link;
        }
        //return $linked;
        //return $object->links;
    }

    /**
     * Get mapping of objects to datastores by looking at the property sources
     *
     * @return array<mixed> of [objectid][datastore] = number of properties
     */
    public static function getMapping()
    {
        // load tables for 'dynamic_data'
        xarMod::loadDbInfo('dynamicdata', 'dynamicdata');
        $xartables =  xarDB::getTables();

        $mapping = [];
        $properties = xarMod::apiFunc(
            'dynamicdata',
            'user',
            'getobjectlist',
            ['name' => 'properties',
                                            'fieldlist' => ['name','objectid','source']]
        );
        $properties->getItems();
        foreach ($properties->items as $item) {
            if (strpos($item['source'], '.') !== false) {
                [$store, $name] = explode('.', $item['source']);
            } elseif ($item['source'] == 'dynamic_data') {
                $store = $xartables['dynamic_data'];
            } else {
                $store = $item['source'];
            }
            if (!isset($mapping[$item['objectid']])) {
                $mapping[$item['objectid']] = [];
            }
            if (!isset($mapping[$item['objectid']][$store])) {
                $mapping[$item['objectid']][$store] = 0;
            }
            $mapping[$item['objectid']][$store] += 1;
        }

        return $mapping;
    }
}
