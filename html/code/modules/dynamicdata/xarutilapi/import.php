<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
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
function dynamicdata_utilapi_import(Array $args=array())
{
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
        xarLog::message('DD: import file ' . $file, xarLog::LEVEL_INFO);
        
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
        
# --------------------------------------------------------
#
# Process an object definition (-def.xml file) 
#
        //FIXME: this unconditionally CLEARS the incoming parameter!!
        $args = array();
        // Get the object's name
        $args['name'] = (string)($xmlobject->attributes()->name);
        xarLog::message('DD: importing ' . $args['name'], xarLog::LEVEL_INFO);

        // check if the object exists
        $info = DataObjectMaster::getObjectInfo(array('name' => $args['name']));
        $dupexists = !empty($info);
        if ($dupexists && !$overwrite) {
            $msg = 'Duplicate definition for #(1) #(2)';
            $vars = array('object',xarVarPrepForDisplay($args['name']));
            throw new DuplicateException(null,$args['name']);
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
            // Load the object properties directly with the values to bypass their setValue methods
            $object->setFieldValues($args,1);
            $objectid = $object->updateItem(array('itemid' => $args['itemid']));
            $objectid = $object->updateItem();
            // remove the properties, as they will be replaced
            $duplicateobject = DataObjectMaster::getObject(array('name' => $info['name']));
            $oldproperties = $duplicateobject->properties;
            foreach ($oldproperties as $propertyitem)
                $dataproperty->deleteItem(array('itemid' => $propertyitem->id));
        } else {
            // Load the object properties directly with the values to bypass their setValue methods
            $object->setFieldValues($args,1);
            $objectid = $object->createItem();
        }

# --------------------------------------------------------
#
# Now process the objects's properties
#
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
            if (!empty($propertyargs['source'])) {
                $propertyargs['source'] = preg_replace("/^xar_/",$prefix,$propertyargs['source']);
            } else {
                $propertyargs['source'] = "";
            }

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

# --------------------------------------------------------
#
# Process an object's items (-dat.xml file) 
#
        $currentobject = "";
        $index = 1;
        $count = count($xmlobject->children());

        // pass on a generic value so that the class(es) will know where we are
        $args['dd_import'] = true;

        foreach($xmlobject->children() as $child) {

            // pass on some generic values so that the class(es) will know where we are
            if ($index == 1) $args['dd_position'] = 'first';
            elseif ($index == $count) $args['dd_position'] = 'last';
            else $args['dd_position'] = '';

            $thisname = $child->getName();
            $args['itemid'] = (!empty($keepitemid)) ? (string)$child->attributes()->itemid : 0;

            // set up the object the first time around in this loop
            if ($thisname != $currentobject) {
                if (!empty($currentobject))
                    throw new Exception("The items imported must all belong to the same object");
                $currentobject = $thisname;

                /*
                // Check that this is a real object
                if (empty($objectnamelist[$currentobject])) {
                    $objectinfo = DataObjectMaster::getObjectInfo(array('name' => $currentobject));
                    if (isset($objectinfo) && !empty($objectinfo['objectid'])) {
                        $objectname2objectid[$currentobject] = $$currentobject;
                    } else {
                        $msg = 'Unknown #(1) "#(2)"';
                        $vars = array('object',xarVarPrepForDisplay($thisname));
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
                    $value = $property->importValue($child);
//                    $value = (string)$child->$propertyname;
                    try {
                        $boolean->validate($value, array());
                    } catch (Exception $e) {
                        try {
                            $integer->validate($value, array());
                        } catch (Exception $e) {}
                    }
                    $object->properties[$propertyname]->value = $value;
                }
            }
            if (empty($keepitemid)) {
                // for dynamic objects, set the primary field to 0 too
                if (isset($object->primary)) {
                    $primary = $object->primary;
                    if (!empty($object->properties[$primary]->value)) {
                        $object->properties[$primary]->value = 0;
                    }
                }
            }

            // for the moment we only allow creates
            // create the item
            $itemid = $object->createItem($args);
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
