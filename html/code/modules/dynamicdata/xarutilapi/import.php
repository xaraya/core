<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Import an object definition or an object item from XML
 *
 * @param $args['file'] location of the .xml file containing the object definition, or
 * @param $args['xml'] XML string containing the object definition
 * @param $args['keepitemid'] (try to) keep the item id of the different items (default false)
 * @param $args['entry'] optional array of external references.
 * @return array object id on success, null on failure
 */
function dynamicdata_utilapi_import($args)
{
    if(!xarSecurityCheck('AdminDynamicData')) return;


    extract($args);

    if (!isset($prefix)) $prefix = xarDB::getPrefix();
    $prefix .= '_';
    if (!isset($overwrite)) $overwrite = false;

    if (empty($xml) && empty($file)) {
        throw new EmptyParameterException('xml or file');
    } elseif (!empty($file) && (!file_exists($file) || !preg_match('/\.xml$/',$file)) ) {
        // check if we tried to load a file using an old path
        if (xarConfigVars::get(null, 'Site.Core.LoadLegacy') == true && strpos($file, 'modules/') === 0) {
            $file = sys::code() . $file;
            if (!file_exists($file)) {
                throw new BadParameterException($file,'Invalid importfile "#(1)"');
            }
        } else {
            throw new BadParameterException($file,'Invalid importfile "#(1)"');
        }
    }

    $objectcache = array();
    $objectmaxid = array();

    $proptypes = DataPropertyMaster::getPropertyTypes();
    $name2id = array();
    foreach ($proptypes as $propid => $proptype) {
        $name2id[$proptype['name']] = $propid;
    }

    if (!empty($file)) {
        $xmlobject = simplexml_load_file($file);
        xarLogMessage('DD: import file ' . $file);
        
    } elseif (!empty($xml)) {
        // remove garbage from the end
        $xml = preg_replace('/>[^<]+$/s','>', $xml);
        $xmlobject = new SimpleXMLElement($xml);
    }
    // No better way of doing this?
    $dom = dom_import_simplexml ($xmlobject);
    $roottag = $dom->tagName;

    sys::import('xaraya.validations');
    $boolean = ValueValidations::get('bool');
    $integer = ValueValidations::get('int');
    
    if ($roottag == 'object') {
        
        //FIXME: this unconditionally CLEARS the incoming parameter!!
        $args = array();
        // Get the object's name
        $args['name'] = (string)($xmlobject->attributes()->name);
        xarLogMessage('DD: importing ' . $args['name']);

        // check if the object exists
        $info = DataObjectMaster::getObjectInfo(array('name' => $args['name']));
        $dupexists = !empty($info);
        if ($dupexists && !$overwrite) {
            $msg = 'Duplicate definition for #(1) #(2)';
            $vars = array('object',xarVarPrepForDisplay($args['name']));
            throw new DuplicateException($vars,$msg);
        }

        $object = DataObjectMaster::getObject(array('name' => 'objects'));
        $objectproperties = array_keys($object->properties);
        foreach($objectproperties as $property) {
            if (isset($xmlobject->{$property}[0])) {
                $value = (string)$xmlobject->{$property}[0];
                try {
                    $boolean->validate($value, array());
                } catch (Exception $e) {
                    try {
                        $integer->validate($value, array());
                    } catch (Exception $e) {}
                }

                $args[$property] = $value;
            }
        }
        // Backwards Compatibility with old definitions
        if (empty($args['moduleid']) && !empty($args['module_id'])) {
            $args['moduleid'] = $args['module_id'];
        }
        if (empty($args['name']) || empty($args['moduleid'])) {
            throw new BadParameterException(null,'Missing keys in object definition');
        }
        // Make sure we drop the object id, because it might already exist here
        //TODO: don't define it in the first place?
        unset($args['objectid']);

        // Add an item to the object
            $args['itemtype'] = xarMod::apiFunc('dynamicdata','admin','getnextitemtype',
                                           array('module_id' => $args['moduleid']));

        // Create the DataProperty object we will use to create items of
        $dataproperty = DataObjectMaster::getObject(array('name' => 'properties'));
        if (empty($dataproperty)) return;

        if ($dupexists && $overwrite) {
            $args['itemid'] = $info['objectid'];
            $args['itemtype'] = $info['itemtype'];
            $objectid = $object->updateItem($args);
            // remove the properties, as they will be replaced
            $duplicateobject = DataObjectMaster::getObject(array('name' => $info['name']));
            $oldproperties = $duplicateobject->properties;
            foreach ($oldproperties as $propertyitem)
                $dataproperty->deleteItem(array('itemid' => $propertyitem->id));
        } else {
            $objectid = $object->createItem($args);
        }

        // Now do the item's properties

        $propertyproperties = array_keys($dataproperty->properties);
        $propertieshead = $xmlobject->properties;
        foreach($propertieshead->children() as $property) {
            $propertyargs = array();
            $propertyname = (string)($property->attributes()->name);
            $propertyargs['name'] = $propertyname;
            foreach($propertyproperties as $prop) {
                if (isset($property->{$prop}[0])) {
                    $value = (string)$property->{$prop}[0];
                    try {
                        $boolean->validate($value, array());
                    } catch (Exception $e) {
                        try {
                            $integer->validate($value, array());
                        } catch (Exception $e) {}
                    }
                    $propertyargs[$prop] = $value;
                }
            }

            // Backwards Compatibility with old definitions
            if (!isset($propertyargs['defaultvalue']) && isset($property->{'default'}[0])) {
                $propertyargs['defaultvalue'] = (string)$property->{'default'}[0];
            }
            if (!isset($propertyargs['seq']) && isset($property->{'order'}[0])) {
                $propertyargs['seq'] = (int)$property->{'order'}[0];
            }
            if (!isset($propertyargs['configuration']) && isset($property->{'validation'}[0])) {
                $propertyargs['configuration'] = (string)$property->{'validation'}[0];
            }

            // Add some args needed to define the property
            unset($propertyargs['id']);
            $propertyargs['objectid'] = $objectid;
            $propertyargs['itemid']   = 0;

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

            // Force a new itemid to be created for this property
            $dataproperty->properties[$dataproperty->primary]->setValue(0);
            // Create the property
            $id = $dataproperty->createItem($propertyargs);
        }

        if (!empty($xmlobject->links)) {
            // make sure that object links are initialized
            sys::import('modules.dynamicdata.class.objects.links');
            $linklist = DataObjectLinks::initLinks();
            if (empty($linklist)) {
                // no object links initialized, bail out
                return $objectid;
            }
            $linkshead = $xmlobject->links;
            $linkprops = array('source','from_prop','target','to_prop','link_type','direction');
            foreach ($linkshead->children() as $link) {
                $info = array();
                foreach ($linkprops as $prop) {
                    if (!isset($link->{$prop}[0])) {
                         unset($info);
                         break;
                    }
                    $info[$prop] = (string)$link->{$prop}[0];
                }
                if (!empty($info)) {
                    // add this link and its reverse if it doesn't exist yet
                    DataObjectLinks::addLink($info['source'],$info['from_prop'],$info['target'],$info['to_prop'],$info['link_type'],$info['direction']);
                }
            }
        }
    } elseif ($roottag == 'items') {

        $currentobject = "";
        $index = 1;
        $count = count($xmlobject->children());

        // pass on a generic value so that the class(es) will know where we are
        $args['import'] = true;

        foreach($xmlobject->children() as $child) {

            // pass on some generic values so that the class(es) will know where we are
            if ($index == 1) $args['position'] = 'first';
            elseif ($index == $count) $args['position'] = 'last';
            else $args['position'] = '';

            $item = array();
            $item['name'] = $child->getName();
            $item['itemid'] = (!empty($keepitemid)) ? (string)$child->attributes()->itemid : 0;

            // set up the object the first time around in this loop
            if ($item['name'] != $currentobject) {
                if (!empty($currentobject))
                    throw new Exception("The items imported must all belong to the same object");
                $currentobject = $item['name'];

                /*
                // Check that this is a real object
                if (empty($objectnamelist[$currentobject])) {
                    $objectinfo = DataObjectMaster::getObjectInfo(array('name' => $currentobject));
                    if (isset($objectinfo) && !empty($objectinfo['objectid'])) {
                        $objectname2objectid[$currentobject] = $$currentobject;
                    } else {
                        $msg = 'Unknown #(1) "#(2)"';
                        $vars = array('object',xarVarPrepForDisplay($item['name']));
                        throw new BadParameterException($vars,$msg);
                    }
                }
                */
                // Create the item
                if (!isset($objectcache[$currentobject])) {
                    $objectcache[$currentobject] = DataObjectMaster::getObject(array('name' => $currentobject));
                }
                $object =& $objectcache[$currentobject];
                $objectid = $objectcache[$currentobject]->objectid;
                // Get the properties for this object
                $objectproperties = $object->properties;
            }

            $oldindex = 0;
            foreach($objectproperties as $propertyname => $property) {
                if (isset($child->$propertyname)) {
                    // Run the import value through the property's validation routine
                    //$check = $property->validateValue((string)$child->$propertyname);
                    $value = (string)$child->$propertyname;
                    try {
                        $boolean->validate($value, array());
                    } catch (Exception $e) {
                        try {
                            $integer->validate($value, array());
                        } catch (Exception $e) {}
                    }
                    $item[$propertyname] = $value;
                }
            }
            if (empty($keepitemid)) {
                // for dynamic objects, set the primary field to 0 too
                if (isset($object->primary)) {
                    $primary = $object->primary;
                    if (!empty($item[$primary])) {
                        $item[$primary] = 0;
                    }
                }
            }
            $args = array_merge($args,$item);

            /* for the moment we only allow creates
            if (!empty($item['itemid'])) {
                // check if the item already exists
                $olditemid = $object->getItem(array('itemid' => $item['itemid']));
                if (!empty($olditemid) && $olditemid == $item['itemid']) {
                    // update the item
                    $itemid = $object->updateItem($args);
                } else {
                    // create the item
                    $itemid = $object->createItem($args);
                }
            } else {
            */
                // create the item
                $itemid = $object->createItem($args);
//            }
            if (empty($itemid)) return;

            // keep track of the highest item id
            //if (empty($objectmaxid[$objectid]) || $objectmaxid[$objectid] < $itemid) {
            //    $objectmaxid[$objectid] = $itemid;
            //}

        }
    }

/* don't think this is needed atm
    // adjust maxid (for objects stored in the dynamic_data table)
    if (count($objectcache) > 0 && count($objectmaxid) > 0) {
        foreach (array_keys($objectcache) as $objectid) {
            if (!empty($objectmaxid[$objectid]) && $object->maxid < $objectmaxid[$objectid]) {
                $itemid = DataObjectMaster::updateObject(array('objectid' => $objectid,
                                                                    'maxid'    => $objectmaxid[$objectid]));
                if (empty($itemid)) return;
            }
        }
        unset($objectcache);
    }
    */
    return $objectid;
}

?>