<?php
/**
 * File: $Id$
 *
 * Dynamic Data Utilities Interface
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/

require_once 'modules/dynamicdata/class/objects.php';

// ----------------------------------------------------------------------
// Some import/export utility functions that don't need to be loaded each time
// ----------------------------------------------------------------------

/**
 * Main menu for utility functions
 */
function dynamicdata_util_main()
{
// Security Check
	if(!securitycheck('Admin')) return;

    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    xarTplSetPageTemplateName('admin');

    return $data;
}

/**
 * Export an object definition or an object item to XML
 */
function dynamicdata_util_export($args)
{
// Security Check
	if(!securitycheck('Admin')) return;

    list($objectid,
         $modid,
         $itemtype,
         $itemid,
         $tofile) = xarVarCleanFromInput('objectid',
                                         'modid',
                                         'itemtype',
                                         'itemid',
                                         'tofile');

    extract($args);

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
                                         'itemid'   => $itemid));

    if (!isset($myobject) || empty($myobject->label)) {
        $data['label'] = xarML('Unknown Object');
        $data['xml'] = '';
        return $data;
    }

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
                $xml .= "  <$name>" . xarVarPrepForDisplay($myobject->$name) . "</$name>\n";
            }
        }
        $xml .= "  <properties>\n";
        foreach (array_keys($myobject->properties) as $name) {
            $xml .= '    <property name="'.$name.'">' . "\n";
            foreach (array_keys($property_properties) as $key) {
                if ($key != 'name' && isset($myobject->properties[$name]->$key)) {
                    $xml .= "      <$key>".$myobject->properties[$name]->$key."</$key>\n";
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


/**
 * Import an object definition or an object item from XML
 */
function dynamicdata_util_import($args)
{
// Security Check
	if(!securitycheck('Admin')) return;

    $import = xarVarCleanFromInput('import');

    extract($args);

    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    $data['warning'] = '';
    $data['options'] = array();
    $data['authid'] = xarSecGenAuthKey();

    $basedir = 'modules/dynamicdata';
    $filetype = 'xml';
    $files = xarModAPIFunc('dynamicdata','admin','browse',
                           array('basedir' => $basedir,
                                 'filetype' => $filetype));
    if (!isset($files) || count($files) < 1) {
        $data['warning'] = xarML('There are currently no XML files available for import in "#(1)"',$basedir);
        return $data;
    }

    if (!empty($import)) {
        if (!xarSecConfirmAuthKey()) return;

        $found = '';
        foreach ($files as $file) {
            if ($file == $import) {
                $found = $file;
                break;
            }
        }
        if (empty($found) || !file_exists($basedir . '/' . $file)) {
            $msg = xarML('File not found');
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException($msg));
            return;
        }
        $objectid = xarModAPIFunc('dynamicdata','util','import',
                                  array('file' => $basedir . '/' . $file));
        if (empty($objectid)) return;

        $objectinfo = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                                    array('objectid' => $objectid));
        if (empty($objectinfo)) return;

        $data['warning'] = xarML('Object #(1) was successfully imported',xarVarPrepForDisplay($objectinfo['label']));
        return $data;
    }

    natsort($files);
    array_unshift($files,'');
    foreach ($files as $file) {
         $data['options'][] = array('id' => $file,
                                    'name' => $file);
    }

    xarTplSetPageTemplateName('admin');

    return $data;
}

/**
 * Return static table information (test only)
 */
function dynamicdata_util_static($args)
{
// Security Check
	if(!securitycheck('Admin')) return;

    list($module,
         $modid,
         $itemtype,
         $table) = xarVarCleanFromInput('module',
                                        'modid',
                                        'itemtype',
                                        'table');

    $export = xarVarCleanFromInput('export');

    extract($args);
    if (empty($export)) {
        $export = 0;
    }

    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    $static = xarModAPIFunc('dynamicdata','util','getstatic',
                            array('module'   => $module,
                                  'modid'    => $modid,
                                  'itemtype' => $itemtype,
                                  'table'    => $table));

    if (!isset($static) || $static == false) {
        $data['tables'] = array();
    } else {
        $data['tables'] = array();
        foreach ($static as $field) {
            if (preg_match('/^(\w+)\.(\w+)$/', $field['source'], $matches)) {
                $table = $matches[1];
                $data['tables'][$table][$field['name']] = $field;
            }
        }
    }

    $data['export'] = $export;
    $data['modid'] = $modid;
    $data['itemtype'] = $itemtype;
    $data['authid'] = xarSecGenAuthKey();

    xarTplSetPageTemplateName('admin');

    return $data;
}

/**
 * Return meta data (test only)
 */
function dynamicdata_util_meta($args)
{
// Security Check
	if(!securitycheck('Admin')) return;

    list($export,
         $table) = xarVarCleanFromInput('export',
                                        'table');

    extract($args);
    if (empty($export)) {
        $export = 0;
    }
    if (empty($table)) {
        $table = '';
    }

    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    $data['tables'] = xarModAPIFunc('dynamicdata','util','getmeta',
                                    array('table' => $table));

    $data['export'] = $export;

    xarTplSetPageTemplateName('admin');

    return $data;
}

/**
 * Return relationship information (test only)
 */
function dynamicdata_util_relations($args)
{
// Security Check
	if(!securitycheck('Admin')) return;

    list($module,
         $modid,
         $itemtype,
         $table) = xarVarCleanFromInput('module',
                                        'modid',
                                        'itemtype',
                                        'table');

    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    // (try to) get the relationships between this module and others
    $data['relations'] = xarModAPIFunc('dynamicdata','util','getrelations',
                                       array('modid' => $modid,
                                             'itemtype' => $itemtype));
    if (!isset($data['relations']) || $data['relations'] == false) {
        $data['relations'] = array();
    }

    xarTplSetPageTemplateName('admin');

    return $data;
}

/**
 * Import the dynamic properties for a module + itemtype from a static table
 */
function dynamicdata_util_importprops()
{
// Security Check
	if(!securitycheck('Admin')) return;

    list($objectid,
         $modid,
         $itemtype,
         $table) = xarVarCleanFromInput('objectid',
                                        'modid',
                                        'itemtype',
                                        'table');
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module id', 'util', 'importprop', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    // Confirm authorisation code.  This checks that the form had a valid
    // authorisation code attached to it.  If it did not then the function will
    // proceed no further as it is possible that this is an attempt at sending
    // in false data to the system
    if (!xarSecConfirmAuthKey()) return;

    if (!xarModAPIFunc('dynamicdata','util','importproperties',
                       array('modid' => $modid,
                             'itemtype' => $itemtype,
                             'table' => $table,
                             'objectid' => $objectid))) {
        return;
    }

    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'modifyprop',
                                  array('modid' => $modid,
                                        'itemtype' => $itemtype)));
}

?>
