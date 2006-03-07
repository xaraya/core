<?php
/**
 * Import an object definition or an object item from XML
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002-2006 The Digital Development Foundation
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
 * @return array object id on success, null on failure
 * @todo MichelV <1> add a check for already present definitions
                     so the errors get more gracious
                 <2> make sure an error doesn't kill the process, but offers a return option
 */
function dynamicdata_utilapi_import($args)
{
    // restricted to DD Admins
// Security Check
    if(!xarSecurityCheck('AdminDynamicData')) return;

    extract($args);

    if (empty($xml) && empty($file)) {
        $msg = xarML('Missing import file or XML content');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    } elseif (!empty($file) && (!file_exists($file) || !preg_match('/\.xml$/',$file)) ) {
        $msg = xarML('Invalid import file');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
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
        if (!$fp) {
            $msg = xarML('Unable to open import file');
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException($msg));
            return;
        }
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
                $object['name'] = $matches[1];
                $what = 'object';
            } elseif (preg_match('#<items>#',$line)) { // in case we only import data
                $what = 'item';
            }

         } elseif ($what == 'object') {
            if (preg_match('#<([^>]+)>(.*)</\1>#',$line,$matches)) {
                $key = $matches[1];
                $value = $matches[2];
                if (isset($object[$key])) {
                    $msg = xarML('Duplicate definition for #(1) key #(2) on line #(3)','object',xarVarPrepForDisplay($key),$count);
                    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
                }
                $object[$key] = strtr($value,$specialchars);
            } elseif (preg_match('#<config>#',$line)) {
                if (isset($object['config'])) {
                    $msg = xarML('Duplicate definition for #(1) key #(2) on line #(3)','object','config',$count);
                    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
                }
                $config = array();
                $what = 'config';
            } elseif (preg_match('#<properties>#',$line)) {
                // let's create the object now...
                if (empty($object['name']) || empty($object['moduleid'])) {
                    $msg = xarML('Missing keys in object definition');
                    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
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
                    $msg = xarML('Duplicate definition for #(1) key #(2) on line #(3)','config',xarVarPrepForDisplay($key),$count);
                    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
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
                    $msg = xarML('Missing keys in property definition');
                    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
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
                    // TODO: make sure we do not encounter this error when there are duplicate labels due to spaces
                    $msg = xarML('Duplicate definition for #(1) key #(2) on line #(3)','property',xarVarPrepForDisplay($key),$count);
                    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
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
                        $msg = xarML('Unknown #(1) "#(2)" on line #(3)','object',xarVarPrepForDisplay($objectname),$count);
                        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                        new SystemException($msg));
                        fclose($fp);
                        return;
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
                    $msg = xarML('Duplicate definition for #(1) key #(2) on line #(3)','item',xarVarPrepForDisplay($key),$count);
                    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
                }
                $item[$key] = strtr($value,$specialchars);
                $closetag = 'N/A';
            } elseif (preg_match('#<([^/>]+)>(.*)#',$line,$matches)) {
                // multi-line entries *are* relevant here
                $key = $matches[1];
                $value = $matches[2];
                if (isset($item[$key])) {
                    $msg = xarML('Duplicate definition for #(1) key #(2)','item',xarVarPrepForDisplay($key));
                    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
                }
                $item[$key] = strtr($value,$specialchars);
                $closetag = $key;
            } elseif (preg_match("#(.*)</$closetag>#",$line,$matches)) {
                // multi-line entries *are* relevant here
                $value = $matches[1];
                if (!isset($item[$closetag])) {
                    $msg = xarML('Undefined #(1) key #(2)','item',xarVarPrepForDisplay($closetag));
                    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
                }
                $item[$closetag] .= strtr($value,$specialchars);
                $closetag = 'N/A';
            } elseif ($closetag != 'N/A') {
                // multi-line entries *are* relevant here
                if (!isset($item[$closetag])) {
                    $msg = xarML('Undefined #(1) key #(2)','item',xarVarPrepForDisplay($closetag));
                    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
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