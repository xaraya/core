<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Import an object definition or an object item from XML
 *
 * @param $args['file'] location of the .xml file containing the object definition, or
 * @param $args['xml'] XML string containing the object definition
 * @param $args['keepitemid'] (try to) keep the item id of the different items (default false)
 * @param $args['objectname'] optional name to override object name we're importing.
 * @returns mixed
 * @return object id on success, null on failure
 */
function dynamicdata_utilapi_import($args)
{
    // restricted to DD Admins
// Security Check
    if(!xarSecurityCheck('AdminDynamicData')) return;

    extract($args);

    if (empty($xml) && empty($file)) 
        throw new EmptyParameterException('xml or file');
    } elseif (!empty($file) && (!file_exists($file) || !preg_match('/\.xml$/',$file)) ) {
        throw new BadParameterException($file,'Invalid importfile "#(1)"');
    }

    $proptypes = xarModAPIFunc('dynamicdata','user','getproptypes');
    $name2id = array();
    foreach ($proptypes as $propid => $proptype) {
        $name2id[$proptype['name']] = $propid;
    }

    $prefix = xarDBGetSystemTablePrefix();
    $prefix .= '_';

    $specialchars = array('&gt;' => '>',
                          '&lt;' => '<',
                          '&quot;' => '"',
                          '&amp;' => '&');

    if (!empty($file)) {
        $fp = @fopen($file, 'r');
        if (!$fp) throw new BadParameterException($file,'Unable to open file "#(1)" for reading.');
    } else {
        $lines = preg_split("/\r?\n/", $xml);
        $maxcount = count($lines);
    }

    $what = '';
    $count = 0;
    $objectname2objectid = array();
    $objectcache = array();
    $objectmaxid = array();
    $closeitem = 'N/A';
    $closetag = 'N/A';
    while ( (!empty($file) && !feof($fp)) || (!empty($xml) && $count < $maxcount) ) {
        if (!empty($file)) {
            $line = fgets($fp, 4096);
        } else {
            $line = $lines[$count];
        }
        $count++;
        if (empty($what)) {
            if (preg_match('#<object name="(\w+)">#',$line,$matches)) { // in case we import the object definition
                $object = array();
                if(empty($objectname)) {
                    $object['name'] = $matches[1];
                } else {
                    // Overide was passed in thru $args
                    $object['name'] = $objectname;
                }
                $what = 'object';
            } elseif (preg_match('#<items>#',$line)) { // in case we only import data
                $what = 'item';
            }

         } elseif ($what == 'object') {
            if (preg_match('#<([^>]+)>(.*)</\1>#',$line,$matches)) {
                $key = $matches[1];
                $value = $matches[2];
                if (isset($object[$key])) {
                    fclose($fp);
                    $msg = 'Duplicate definition for #(1) key #(2) on line #(3)';
                    $vars = array('object',xarVarPrepForDisplay($key),$count);
                    throw new DuplicateException($vars,$msg);
                }
                $object[$key] = strtr($value,$specialchars);
            } elseif (preg_match('#<config>#',$line)) {
                if (isset($object['config'])) {
                    fclose($fp);
                    $msg = 'Duplicate definition for #(1) key #(2) on line #(3)';
                    $vars = array('object','config',$count);
                    throw new DuplicateException($vars,$msg);
                }
                $config = array();
                $what = 'config';
            } elseif (preg_match('#<properties>#',$line)) {
                // let's create the object now...
                if (empty($object['name']) || empty($object['moduleid'])) {
                    fclose($fp);
                    throw new BadParameterException(null,'Missing keys in object definition');
                }
                // make sure we drop the object id, because it might already exist here
                unset($object['objectid']);

                // for objects that belong to dynamicdata itself, reset the itemtype too
                if ($object['moduleid'] == xarModGetIDFromName('dynamicdata')) {
                    $object['itemtype'] = -1;
                }

                
                $objectid = xarModAPIFunc('dynamicdata','admin','createobject',
                                          $object);
                if (!isset($objectid)) {
                    fclose($fp);
                    return;
                }

                // retrieve the correct itemtype if necessary
                if ($object['itemtype'] < 0) {
                    $objectinfo = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                                                array('objectid' => $objectid));
                    $object['itemtype'] = $objectinfo['itemtype'];
                }

                $what = 'property';
            } elseif (preg_match('#<items>#',$line)) {
                $what = 'item';
            } elseif (preg_match('#</object>#',$line)) {
                $what = '';
            } else {
                // multi-line entries not relevant here
            }

        } elseif ($what == 'config') {
            if (preg_match('#<([^>]+)>(.*)</\1>#',$line,$matches)) {
                $key = $matches[1];
                $value = $matches[2];
                if (isset($config[$key])) {
                    $msg = 'Duplicate definition for #(1) key #(2) on line #(3)';
                    $vars = array('config',xarVarPrepForDisplay($key),$count);
                    fclose($fp);
                    throw new DuplicateException($vars,$msg);
                }
                $config[$key] = strtr($value,$specialchars);
            } elseif (preg_match('#</config>#',$line)) {
                $object['config'] = serialize($config);
                $config = array();
                $what = 'object';
            } else {
                // multi-line entries not relevant here
            }

        } elseif ($what == 'property') {
            if (preg_match('#<property name="(\w+)">#',$line,$matches)) {
                $property = array();
                $property['name'] = $matches[1];
            } elseif (preg_match('#</property>#',$line)) {
                // let's create the property now...
                $property['objectid'] = $objectid;
                $property['moduleid'] = $object['moduleid'];
                $property['itemtype'] = $object['itemtype'];
                if (empty($property['name']) || empty($property['type'])) {
                    fclose($fp);
                    throw new BadParameterException(null,'Missing keys in property definition');
                }
                // make sure we drop the property id, because it might already exist here
                unset($property['id']);
                // convert property type to numeric if necessary
                if (!is_numeric($property['type'])) {
                    if (isset($name2id[$property['type']])) {
                        $property['type'] = $name2id[$property['type']];
                    } else {
                        $property['type'] = 1;
                    }
                }
            // TODO: watch out for multi-sites
                // replace default xar_* table prefix with local one
                $property['source'] = preg_replace("/^xar_/",$prefix,$property['source']);

                $prop_id = xarModAPIFunc('dynamicdata','admin','createproperty',
                                         $property);
                if (!isset($prop_id)) {
                    fclose($fp);
                    return;
                }
            } elseif (preg_match('#<([^>]+)>(.*)</\1>#',$line,$matches)) {
                $key = $matches[1];
                $value = $matches[2];
                if (isset($property[$key])) {
                    fclose($fp);
                    $msg = 'Duplicate definition for #(1) key #(2) on line #(3)';
                    $vars = array('property',xarVarPrepForDisplay($key),$count);
                    throw new DuplicateException($vars,$msg);
                }
                $property[$key] = strtr($value,$specialchars);
            } elseif (preg_match('#</properties>#',$line)) {
                $what = 'object';
            } elseif (preg_match('#<items>#',$line)) {
                $what = 'item';
            } elseif (preg_match('#</object>#',$line)) {
                $what = '';
            } else {
                // multi-line entries not relevant here
            }

        } elseif ($what == 'item') {
            if (preg_match('#<([^> ]+) itemid="(\d+)">#',$line,$matches)) {
                // find out what kind of item we're dealing with
                $objectname = $matches[1];
                $itemid = $matches[2];
                if (empty($objectname2objectid[$objectname])) {
                    $objectinfo = Dynamic_Object_Master::getObjectInfo(array('name' => $objectname));
                    if (isset($objectinfo) && !empty($objectinfo['objectid'])) {
                        $objectname2objectid[$objectname] = $objectinfo['objectid'];
                    } else {
                        $msg = 'Unknown #(1) "#(2)" on line #(3)';
                        $vars = array('object',xarVarPrepForDisplay($objectname),$count);
                        fclose($fp);
                        throw new BadParameterException($vars,$msg);
                    }
                }
                $objectid = $objectname2objectid[$objectname];
                $item = array();
                if (!empty($keepitemid)) {
                    $item['itemid'] = $itemid;
                }
                $closeitem = $objectname;
                $closetag = 'N/A';
            } elseif (preg_match("#</$closeitem>#",$line)) {
                // let's create the item now...
                if (!isset($objectcache[$objectid])) {
                    $objectcache[$objectid] = & Dynamic_Object_Master::getObject(array('objectid' => $objectid));
                }
                if (empty($keepitemid)) {
                    // set the item id to 0
                    $item['itemid'] = 0;
                    // for dynamic objects, set the primary field to 0 too
                    if (isset($objectcache[$objectid]->primary)) {
                        $primary = $objectcache[$objectid]->primary;
                        if (!empty($item[$primary])) {
                            $item[$primary] = 0;
                        }
                    }
                }
                // create the item
                $itemid = $objectcache[$objectid]->createItem($item);
                if (empty($itemid)) {
                    fclose($fp);
                    return;
                }
                // keep track of the highest item id
                if (empty($objectmaxid[$objectid]) || $objectmaxid[$objectid] < $itemid) {
                    $objectmaxid[$objectid] = $itemid;
                }
                $closeitem = 'N/A';
                $closetag = 'N/A';
            } elseif (preg_match('#<([^>]+)>(.*)</\1>#',$line,$matches)) {
                $key = $matches[1];
                $value = $matches[2];
                if (isset($item[$key])) {
                    $msg = 'Duplicate definition for #(1) key #(2) on line #(3)';
                    $vars = array('item',xarVarPrepForDisplay($key),$count);
                    fclose($fp);
                    throw new DuplicateException($vars,$msg);
                }
                $item[$key] = strtr($value,$specialchars);
                $closetag = 'N/A';
            } elseif (preg_match('#<([^/>]+)>(.*)#',$line,$matches)) {
                // multi-line entries *are* relevant here
                $key = $matches[1];
                $value = $matches[2];
                if (isset($item[$key])) {
                    $msg = 'Duplicate definition for #(1) key #(2)';
                    $vars = array('item',xarVarPrepForDisplay($key));
                    fclose($fp);
                    throw new DuplicateException($vars,$msg);
                }
                $item[$key] = strtr($value,$specialchars);
                $closetag = $key;
            } elseif (preg_match("#(.*)</$closetag>#",$line,$matches)) {
                // multi-line entries *are* relevant here
                $value = $matches[1];
                if (!isset($item[$closetag])) {
                    $msg = 'Undefined #(1) key #(2)';
                    $vars = array('item',xarVarPrepForDisplay($closetag));
                    fclose($fp);
                    throw new BadParameterException($vars,$msg);
                }
                $item[$closetag] .= strtr($value,$specialchars);
                $closetag = 'N/A';
            } elseif ($closetag != 'N/A') {
                // multi-line entries *are* relevant here
                if (!isset($item[$closetag])) {
                    $msg = 'Undefined #(1) key #(2)';
                    $vars = array('item',xarVarPrepForDisplay($closetag));
                    fclose($fp);
                    throw new BadParameterException($vars,$msg);
                }
                $item[$closetag] .= strtr($line,$specialchars);
            } elseif (preg_match('#</items>#',$line)) {
                $what = 'object';
            } elseif (preg_match('#</object>#',$line)) {
                $what = '';
            } else {
            }
        } else {
        }
    }
    if (!empty($file)) {
        fclose($fp);
    }

    // adjust maxid (for objects stored in the dynamic_data table)
    if (count($objectcache) > 0 && count($objectmaxid) > 0) {
        foreach (array_keys($objectcache) as $objectid) {
            if (!empty($objectmaxid[$objectid]) && $objectcache[$objectid]->maxid < $objectmaxid[$objectid]) {
                $itemid = Dynamic_Object_Master::updateObject(array('objectid' => $objectid,
                                                                    'maxid'    => $objectmaxid[$objectid]));
                if (empty($itemid)) return;
            }
        }
    }

    return isset($objectid) ? $objectid : null;
}

?>
