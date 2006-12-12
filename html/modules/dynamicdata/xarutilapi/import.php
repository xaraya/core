<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Import an object definition or an object item from XML
 *
 * @param $args['file'] location of the .xml file containing the object definition, or
 * @param $args['xml'] XML string containing the object definition
 * @param $args['keepitemid'] (try to) keep the item id of the different items (default false)
 * @param $args['objectname'] optional name to override object name we're importing.
 * @param $args['entry'] optional array of external references.
 * @return array object id on success, null on failure
 * @todo MichelV <1> add a check for already present definitions
                     so the errors get more gracious
                 <2> make sure an error doesn't kill the process, but offers a return option
 */
function dynamicdata_utilapi_import($args)
{
// Security Check
    if(!xarSecurityCheck('AdminDynamicData')) return;

    extract($args);

    if (!isset($prefix)) $prefix = xarDBGetSiteTablePrefix() . "_";
    else $prefix .= "_";
// TODO: check this
// if (!isset($prefix)) $prefix = xarDBGetSystemTablePrefix() . "_";
    if (empty($xml) && empty($file)) {
        throw new EmptyParameterException('xml or file');
    } elseif (!empty($file) && (!file_exists($file) || !preg_match('/\.xml$/',$file)) ) {
        throw new BadParameterException($file,'Invalid importfile "#(1)"');
    }
    if (!isset($entry) || empty($entry) || !is_array($entry)) $entry = array();

    $objectcache = array();
    $objectmaxid = array();
    $proptypes = DataPropertyMaster::getPropertyTypes();
    $name2id = array();
    foreach ($proptypes as $propid => $proptype) {
        $name2id[$proptype['name']] = $propid;
    }

    if (!empty($file)) {
        $xmlobject = simplexml_load_file($file);
    } elseif (!empty($xml)) {
        $xmlobject = new SimpleXMLElement($xml);
    }
    // No better way of doing this?
    $dom = dom_import_simplexml ($xmlobject);
    $roottag = $dom->tagName;

    if ($roottag == 'object') {

        $args = array();
        // Get the object's name
        $args['name'] = (string)($xmlobject->attributes()->name);

        $object = DataObjectMaster::getObject(array('objectid' => 1));
        $objectproperties = array_keys($object->properties);
        foreach($objectproperties as $property) {
            if (isset($xmlobject->{$property}[0]))
                $args[$property] = (string)$xmlobject->{$property}[0];
        }

        // Now do some checking
        /*
        if (isset($object[$key])) {
            fclose($fp);
            $msg = 'Duplicate definition for #(1) key #(2) on line #(3)';
            $vars = array('object',xarVarPrepForDisplay($key),$count);
            throw new DuplicateException($vars,$msg);
        }
        */
        // Treat parents where the module is DD differently. Put in numeric itemtype
        if ($args['moduleid'] == 182) {
            $info = DataObjectMaster::getObjectInfo(array('name' => $args['parent']));
            $args['parent'] = $info['itemtype'];
        }
        if (empty($args['name']) || empty($args['moduleid'])) {
            throw new BadParameterException(null,'Missing keys in object definition');
        }
        // Make sure we drop the object id, because it might already exist here
        //TODO: don't define it in the first place?
        unset($args['objectid']);

        // Get the DataObject Objects
        $myobject = DataObjectMaster::getObject();

        // Add an item to the object
        if ($args['moduleid'] == 182) {
            $args['itemtype'] = xarModAPIFunc('dynamicdata','admin','getnextitemtype',
                                           array('modid' => $args['moduleid']));
        }
        $objectid = $myobject->createItem($args);

        // Now do the item's properties
        $property = DataObjectMaster::getObject(array('objectid' => 2));
        $propertyproperties = array_keys($property->properties);
        $propertieshead = $xmlobject->properties;
        foreach($propertieshead->children() as $property) {
            $propertyname = (string)($property->attributes()->name);
            $propertyargs['name'] = $propertyname;
            foreach($propertyproperties as $prop) {
                if (isset($property->{$prop}[0]))
                    $propertyargs[$prop] = (string)$property->{$prop}[0];
            }

            // Add some args needed to define the property
            unset($propertyargs['id']);
            $propertyargs['objectid'] = $objectid;
            $propertyargs['moduleid'] = $args['moduleid'];
            $propertyargs['itemtype'] = $args['itemtype'];

            // Now do some checking
            if (empty($propertyargs['name']) || empty($propertyargs['type'])) {
                throw new BadParameterException(null,'Missing keys in property definition');
            }
            // convert property type to numeric if necessary
            if (!is_numeric($propertyargs['type'])) {
                if (isset($name2id[$propertyargs['type']])) {
                    $propertyargs['type'] = $name2id[$propertyargs['type']];
                } else {
                    $propertyargs['type'] = 1;
                }
            }
            // TODO: watch out for multi-sites
            // replace default xar_* table prefix with local one
            $propertyargs['source'] = preg_replace("/^xar_/",$prefix,$propertyargs['source']);

            // Create this property
            $myobject = DataObjectMaster::getObject(array(
                                                 'itemtype'   => 1,
                                                 'itemid'   => 0));
            if (empty($myobject)) return;
            $prop_id = $myobject->createItem($propertyargs);
        }
    } elseif ($roottag == 'items') {

        $indices = array();
        foreach($xmlobject->children() as $child) {
            $item = array();
            $item['name'] = $child->getName();
            $item['itemid'] = (!empty($keepitemid)) ? (string)$child->attributes()->itemid : 0;

            if (empty($objectname2objectid[$item['name']])) {
                $objectinfo = DataObjectMaster::getObjectInfo(array('name' => $item['name']));
                if (isset($objectinfo) && !empty($objectinfo['objectid'])) {
                    $objectname2objectid[$item['name']] = $objectinfo['objectid'];
                } else {
                    $msg = 'Unknown #(1) "#(2)"';
                    $vars = array('object',xarVarPrepForDisplay($item['name']));
                    throw new BadParameterException($vars,$msg);
                }
            }
            $objectid = $objectname2objectid[$item['name']];

            // Get the properties for this object
            $object = DataObjectMaster::getObject(array('objectid'   => $objectid));
            $primaryobject = DataObjectMaster::getObject(array('objectid' => $object->baseancestor));
            $objectproperties = $object->properties;
            $oldindex = 0;
            foreach($objectproperties as $propertyname => $property) {
                if (isset($child->$propertyname)) {
                    $value = (string)$child->$propertyname;
                    if ($property->type == 30049) {
                        if (in_array($value,array_keys($indices))) {
                            $item[$propertyname] = $indices[$value];
                        } else {
                            if (count($entry > 0)) {
                                $entryvalue = array_shift($entry);
                                $item[$propertyname] = $entryvalue;
                                $indices[$value] = $entryvalue;
                            } else {
                                $item[$propertyname] = 0;
                            }
                            $item[$propertyname] = $indices[$value];
                        }
                    } else {
                        $item[$propertyname] = $value;
                    }
                }
                if($propertyname == $primaryobject->primary) $oldindex = $item[$propertyname];
            }
            // Create the item
            if (!isset($objectcache[$objectid])) {
                $objectcache[$objectid] = DataObjectMaster::getObject(array('objectid' => $objectid));
            }
            if (empty($keepitemid)) {
                // for dynamic objects, set the primary field to 0 too
                if (isset($objectcache[$objectid]->primary)) {
                    $primary = $objectcache[$objectid]->primary;
                    if (!empty($item[$primary])) {
                        $item[$primary] = 0;
                    }
                }
            }
            if (!empty($item['itemid'])) {
                // check if the item already exists
                $olditemid = $objectcache[$objectid]->getItem(array('itemid' => $item['itemid']));
                if (!empty($olditemid) && $olditemid == $item['itemid']) {
                    // update the item
                    $itemid = $objectcache[$objectid]->updateItem($item);
                } else {
                    // create the item
                    $itemid = $objectcache[$objectid]->createItem($item);
                }
            } else {
                // create the item
                $itemid = $objectcache[$objectid]->createItem($item);
            }
            if (empty($itemid)) return;

            // add the new index to the array of indices for reference
            $indices[$oldindex] = $itemid;
            // keep track of the highest item id
            if (empty($objectmaxid[$objectid]) || $objectmaxid[$objectid] < $itemid) {
                $objectmaxid[$objectid] = $itemid;
            }

        }
    }

    // adjust maxid (for objects stored in the dynamic_data table)
    if (count($objectcache) > 0 && count($objectmaxid) > 0) {
        foreach (array_keys($objectcache) as $objectid) {
            if (!empty($objectmaxid[$objectid]) && $objectcache[$objectid]->maxid < $objectmaxid[$objectid]) {
                $itemid = DataObjectMaster::updateObject(array('objectid' => $objectid,
                                                                    'maxid'    => $objectmaxid[$objectid]));
                if (empty($itemid)) return;
            }
        }
        unset($objectcache);
    }

    return isset($objectid) ? $objectid : null;
}

?>
