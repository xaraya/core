<?php
/**
 * File: $Id$
 *
 * Export an object definition or an object item to XML
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/
/**
 * Export an object definition or an object item to XML
 */
function dynamicdata_util_export($args)
{
// Security Check
    if(!xarSecurityCheck('AdminDynamicData')) return;

    extract($args);

    if(!xarVarFetch('objectid', 'isset', $objectid, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',   'isset', $itemid,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tofile',   'isset', $tofile,   NULL, XARVAR_DONT_SET)) {return;}

    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    $myobject = new Dynamic_Object(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid,
                                         'allprops' => true));

    if (!isset($myobject) || empty($myobject->label)) {
        $data['label'] = xarML('Unknown Object');
        $data['xml'] = '';
        return $data;
    }

    $proptypes = xarModAPIFunc('dynamicdata','user','getproptypes');

    $prefix = xarDBGetSystemTablePrefix();
    $prefix .= '_';

    $xml = '';

    // export object definition
    if (empty($itemid)) {
        $data['label'] = xarML('Export Object Definition for #(1)', $myobject->label);

        // get the list of properties for a Dynamic Object
        $object_properties = Dynamic_Property_Master::getProperties(array('objectid' => 1));

        // get the list of properties for a Dynamic Property
        $property_properties = Dynamic_Property_Master::getProperties(array('objectid' => 2));

        $xml .= '<object name="'.$myobject->name.'">'."\n";
        foreach (array_keys($object_properties) as $name) {
            if ($name != 'name' && isset($myobject->$name)) {
                if (is_array($myobject->name)) {
                    $xml .= "  <$name>\n";
                    foreach ($myobject->$name as $field => $value) {
                        $xml .= "    <$field>" . xarVarPrepForDisplay($value) . "</$field>\n";
                    }
                    $xml .= "  </$name>\n";
                } else {
                    $xml .= "  <$name>" . xarVarPrepForDisplay($myobject->$name) . "</$name>\n";
                }
            }
        }
        $xml .= "  <properties>\n";
        foreach (array_keys($myobject->properties) as $name) {
            $xml .= '    <property name="'.$name.'">' . "\n";
            foreach (array_keys($property_properties) as $key) {
                if ($key != 'name' && isset($myobject->properties[$name]->$key)) {
                    if ($key == 'type') {
                        // replace numeric property type with text version
                        $xml .= "      <$key>".xarVarPrepForDisplay($proptypes[$myobject->properties[$name]->$key]['name'])."</$key>\n";
                    } elseif ($key == 'source') {
                        // replace local table prefix with default xar_* one
                        $val = $myobject->properties[$name]->$key;
                        $val = preg_replace("/^$prefix/",'xar_',$val);
                        $xml .= "      <$key>".xarVarPrepForDisplay($val)."</$key>\n";
                    } else {
                        $xml .= "      <$key>".xarVarPrepForDisplay($myobject->properties[$name]->$key)."</$key>\n";
                    }
                }
            }
            $xml .= "    </property>\n";
        }
        $xml .= "  </properties>\n";
        $xml .= "</object>\n";

        $data['formlink'] = xarModURL('dynamicdata','util','export',
                                      array('objectid' => $myobject->objectid,
                                            'itemid'   => 'all'));
        $data['filelink'] = xarModURL('dynamicdata','util','export',
                                      array('objectid' => $myobject->objectid,
                                            'itemid'   => 'all',
                                            'tofile'   => 1));

    // export specific item
    } elseif (is_numeric($itemid)) {
        $data['label'] = xarML('Export Data for #(1) # #(2)', $myobject->label, $itemid);

        $myobject->getItem();

        $xml .= '<'.$myobject->name.' itemid="'.$itemid.'">'."\n";
        foreach (array_keys($myobject->properties) as $name) {
            $xml .= "  <$name>" . xarVarPrepForDisplay($myobject->properties[$name]->value) . "</$name>\n";
        }
        $xml .= '</'.$myobject->name.">\n";

    // export all items (better save this to file, e.g. in var/cache/...)
    } elseif ($itemid == 'all') {
        $data['label'] = xarML('Export Data for all #(1) Items', $myobject->label);

        $mylist = new Dynamic_Object_List(array('objectid' => $objectid,
                                                'moduleid' => $modid,
                                                'itemtype' => $itemtype));
        $mylist->getItems();

        if (empty($tofile)) {
            $xml .= "<items>\n";
            foreach ($mylist->items as $itemid => $item) {
                $xml .= '  <'.$mylist->name.' itemid="'.$itemid.'">'."\n";
                foreach (array_keys($mylist->properties) as $name) {
                    if (isset($item[$name])) {
                        $xml .= "    <$name>" . xarVarPrepForDisplay($item[$name]) . "</$name>\n";
                    } else {
                        $xml .= "    <$name></$name>\n";
                    }
                }
                $xml .= '  </'.$mylist->name.">\n";
            }
            $xml .= "</items>\n";

        } else {
            $varDir = xarCoreGetVarDirPath();
            $outfile = $varDir . '/cache/templates/' . xarVarPrepForOS($mylist->name) . '.data.xml';
            $fp = @fopen($outfile,'w');
            if (!$fp) {
                $data['xml'] = xarML('Unable to open file');
                return $data;
            }
            fputs($fp, "<items>\n");
            foreach ($mylist->items as $itemid => $item) {
                fputs($fp, "  <".$mylist->name." itemid=\"$itemid\">\n");
                foreach (array_keys($mylist->properties) as $name) {
                    if (isset($item[$name])) {
                        fputs($fp, "    <$name>" . xarVarPrepForDisplay($item[$name]) . "</$name>\n");
                    } else {
                        fputs($fp, "    <$name></$name>\n");
                    }
                }
                fputs($fp, "  </".$mylist->name.">\n");
            }
            fputs($fp, "</items>\n");
            fclose($fp);
            $xml .= xarML('Data saved to #(1)',$outfile);
        }

    } else {
        $data['label'] = xarML('Unknown Request for #(1)', $label);
        $xml = '';
    }

    $data['xml'] = xarVarPrepForDisplay($xml);

    xarTplSetPageTemplateName('admin');

    return $data;
}


?>
