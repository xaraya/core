<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: mikespub
// Purpose of file:  Dynamic Data user API
// ----------------------------------------------------------------------

/**
 * get all dynamic data fields for an item
 * (identified by module + item type + item id)
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item fields to get, or
 * @param $args['modid'] module id of the item fields to get
 * @param $args['itemtype'] item type of the item fields to get
 * @param $args['itemid'] item id of the item fields to get
 * @returns array
 * @return array of fields, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getall($args)
{
    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'getall', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:$itemid", ACCESS_OVERVIEW)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype));
    if (empty($fields) || count($fields) == 0) {
        return array();
    }

    $ids = array_keys($fields);

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicdata = $xartable['dynamic_data'];
    $dynamicprop = $xartable['dynamic_properties'];

    $sql = "SELECT xar_dd_propid,
                   xar_dd_value
             FROM $dynamicdata
            WHERE xar_dd_propid IN (" . join(', ',$ids) . ")
              AND xar_dd_itemid = " . xarVarPrepForStore($itemid);
    $result = $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    while (!$result->EOF) {
        list($id, $value) = $result->fields;
        if (isset($value)) {
            $fields[$id]['value'] = $value;
        }
        $result->MoveNext();
    }

    $result->Close();

    foreach ($fields as $id => $field) {
        if (xarSecAuthAction(0, 'DynamicData::Field', $field['label'].':'.$field['type'].':'.$id, ACCESS_READ)) {
            if (!isset($field['value'])) {
                $fields[$id]['value'] = $fields[$id]['default'];
            }
        } else {
            unset($fields[$id]);
        }
    }

    return $fields;
}

/**
 * get a specific item field
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item field to get, or
 * @param $args['modid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @param $args['itemid'] item id of the item field to get
 * @param $args['prop_id'] property id of the field to get, or
 * @param $args['name'] name of the field to get
 * @returns mixed
 * @return value of the field, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_get($args)
{
    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if ((!isset($name) && !isset($prop_id)) ||
        (isset($name) && !is_string($name)) ||
        (isset($prop_id) && !is_numeric($prop_id))) {
        $invalid[] = 'field name or property id';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'get', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicdata = $xartable['dynamic_data'];
    $dynamicprop = $xartable['dynamic_properties'];

    $sql = "SELECT xar_prop_label,
                   xar_prop_dtype,
                   xar_prop_id,
                   xar_prop_default,
                   xar_dd_value
            FROM $dynamicdata, $dynamicprop
            WHERE xar_prop_id = xar_dd_propid
              AND xar_prop_moduleid = " . xarVarPrepForStore($modid);
    if (!empty($itemtype)) {
        $sql .= " AND xar_prop_itemtype = " . xarVarPrepForStore($itemtype);
    }
    $sql .= " AND xar_dd_itemid = " . xarVarPrepForStore($itemid);
    if (!empty($prop_id)) {
        $sql .= " AND xar_prop_id = " . xarVarPrepForStore($prop_id);
    } else {
        $sql .= " AND xar_prop_label = '" . xarVarPrepForStore($name) . "'";
    }

    $result = $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    if ($result->EOF) {
        $result->Close();
        return;
    }
    list($label, $type, $id, $default, $value) = $result->fields;
    $result->Close();

    if (!xarSecAuthAction(0, 'DynamicData::Field', "$label:$type:$id", ACCESS_READ)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }
    if (!isset($value)) {
        $value = $default;
    }

    return $value;
}

/**
 * get field properties for a specific module + item type
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item fields, or
 * @param $args['modid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @returns mixed
 * @return value of the field, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getprop($args)
{
    static $propertybag = array();

    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'getprop', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($propertybag["$modid:$itemtype"])) {
        return $propertybag["$modid:$itemtype"];
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicprop = $xartable['dynamic_properties'];

    $sql = "SELECT xar_prop_label,
                   xar_prop_dtype,
                   xar_prop_id,
                   xar_prop_default,
                   xar_prop_validation
            FROM $dynamicprop
            WHERE xar_prop_moduleid = " . xarVarPrepForStore($modid) . "
              AND xar_prop_itemtype = " . xarVarPrepForStore($itemtype);

    $result = $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    $fields = array();

    while (!$result->EOF) {
        list($label, $type, $id, $default, $validation) = $result->fields;
        if (xarSecAuthAction(0, 'DynamicData::Field', "$label:$type:$id", ACCESS_READ)) {
            $fields[$id] = array('label' => $label,
                                 'type' => $type,
                                 'id' => $id,
                                 'default' => $default,
                                 'validation' => $validation);
        }
        $result->MoveNext();
    }

    $result->Close();

    $propertybag["$modid:$itemtype"] = $fields;
    return $fields;
}

/**
 * get the list of modules + itemtypes for which properties are defined
 *
 * @author the DynamicData module development team
 * @returns array
 * @return array of modid + itemtype + number of properties
 * @raise DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getmodules($args)
{
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicprop = $xartable['dynamic_properties'];

    $query = "SELECT xar_prop_moduleid,
                     xar_prop_itemtype,
                     COUNT(xar_prop_id)
              FROM $dynamicprop
              GROUP BY xar_prop_moduleid, xar_prop_itemtype";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $modules = array();

    while (!$result->EOF) {
        list($modid, $itemtype, $count) = $result->fields;
        if (xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:", ACCESS_OVERVIEW)) {
            $modules[] = array('modid' => $modid,
                               'itemtype' => $itemtype,
                               'numitems' => $count);
        }
        $result->MoveNext();
    }

    $result->Close();

    return $modules;
}

/**
 * get the list of defined property types from somewhere...
 *
 * @author the DynamicData module development team
 * @returns array
 * @return array of property types
 * @raise DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getproptypes($args)
{
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $proptypes = array();

// TODO: replace with something else
    $name2label = array(
        'static'          => xarML('Static Text'),
        'textbox'         => xarML('Text Box'),
        'textarea_small'  => xarML('Small Text Area'),
        'textarea_medium' => xarML('Medium Text Area'),
        'textarea_large'  => xarML('Large Text Area'),
// TODO: define how to fill this in (cfr. status)
//        'dropdown'        => xarML('Dropdown List'),
        'username'        => xarML('Username'),
        'calendar'        => xarML('Calendar'),
        'fileupload'      => xarML('File Upload'),
        'status'          => xarML('Status'),
        'url'             => xarML('URL'),
        'image'           => xarML('Image'),
        'webpage'         => xarML('HTML Page'),
    );

    $name2num = array(
        'static'          => 1,
        'textbox'         => 2,
        'textarea_small'  => 3,
        'textarea_medium' => 4,
        'textarea_large'  => 5,
// TODO: define how to fill this in (cfr. status)
//        'dropdown'        => 6,
        'username'        => 7,
        'calendar'        => 8,
        'fileupload'      => 9,
        'status'          => 10,
        'url'             => 11,
        'image'           => 12,
        'webpage'         => 13,
    );

    foreach ($name2label as $name => $label) {
        $id = $name2num[$name];
        $proptypes[$id] = array('id' => $id,
                                'name' => $name,
                                'label' => $label,
                                'format' => $id,
                                //...
                               );
    }

// TODO: yes :)
/*
    $dynamicproptypes = $xartable['dynamic_property_types'];

    $query = "SELECT ...
              FROM $dynamicproptypes";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    while (!$result->EOF) {
        list(...) = $result->fields;
        if (xarSecAuthAction(0, '...', "...", ACCESS_OVERVIEW)) {
            $proptypes[] = array(...);
        }
        $result->MoveNext();
    }

    $result->Close();
*/

    return $proptypes;
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-input ...> form field tags
 * Format : <xar:data-input definition="$definition" /> with $definition an array
 *                                             containing the type, name, value, ...
 *       or <xar:data-input name="thisname" type="thattype" value="$val" ... />
 * 
 * @param $args array containing the input field definition or the type, name, value, ...
 * @returns string
 * @return the PHP code needed to invoke showinput() in the BL template
 */
function dynamicdata_userapi_handleInputTag($args)
{
    $out = "xarModAPILoad('dynamicdata','user');
echo xarModAPIFunc('dynamicdata',
                   'user',
                   'showinput',\n";
    if (isset($args['definition'])) {
        $out .= '                   '.$args['definition']."\n";
        $out .= '                  );';
    } else {
        $out .= "                   array(\n";
        foreach ($args as $key => $val) {
            if (is_numeric($val) || substr($val,0,1) == '$') {
                $out .= "                         '$key' => $val,\n";
            } else {
                $out .= "                         '$key' => '$val',\n";
            }
        }
        $out .= "                         ));";
    }
    return $out;
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * show some predefined form input field in a template
 * 
 * @param $args array containing the definition of the field (type, name, value, ...)
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_userapi_showinput($args)
{
    extract($args);
    if (empty($name)) {
        return xarML('Missing \'name\' attribute in field tag or definition');
    }
    if (!isset($type)) {
        $type = 1;
    }
    if (!isset($value)) {
        $value = '';
    }
    if (!isset($id)) {
        $id = '';
    } else {
        $id = ' id="'.$id.'"';
    }
    if (!isset($tabindex)) {
        $tabindex = '';
    } else {
        $tabindex = ' tabindex="'.$tabindex.'"';
    }

// TODO: replace with something else
    $proptypes = xarModAPIFunc('dynamicdata','user','getproptypes');
    if (is_numeric($type)) {
        if (!empty($proptypes[$type]['name'])) {
            $typename = $proptypes[$type]['name'];
        } else {
            return xarML('Unknown property type #(1)',$type);
        }
    } else {
        $typename = $type;
    }

    $output = '';
    switch ($typename) {
        case 'text':
        case 'textbox':
            if (empty($size)) {
                $size = 50;
            }
            $output .= '<input type="text" name="'.$name.'" value="'.$value.'" size="'.$size.'"'.$id.$tabindex.' />';
            break;
        case 'textarea':
        case 'textarea_small':
        case 'textarea_medium':
        case 'textarea_large':
            if (empty($wrap)) {
                $wrap = 'soft';
            }
            if (empty($cols)) {
                $cols = 50;
            }
            if (empty($rows)) {
                if ($typename == 'textarea_small') {
                    $rows = 2;
                } elseif ($typename == 'textarea_large') {
                    $rows = 20;
                } else {
                    $rows = 8;
                }
            }
            $output .= '<textarea name="'.$name.'" wrap="'.$wrap.'" rows="'.$rows.'" cols="'.$cols.'"'.$id.$tabindex.'>'.$value.'</textarea>';
            break;
    // TEST ONLY
        case 'webpage':
            if (!isset($options) || !is_array($options)) {
                $options = array();
            // Load admin API for HTML file browser
                if (!xarModAPILoad('articles', 'admin'))  return 'Unable to load articles admin API';
                //$basedir = '/home/mikespub/www/pictures';
                $basedir = 'd:/backup/mikespub/pictures';
                $filetype = 'html?';
                $files = xarModAPIFunc('articles','admin','browse',
                                       array('basedir' => $basedir,
                                             'filetype' => $filetype));
                natsort($files);
                array_unshift($files,'');
                foreach ($files as $file) {
                    $options[] = array('id' => $file,
                                       'name' => $file);
                }
                unset($files);
            }
            // fall through to the next one
        case 'status':
            if (!isset($options) || !is_array($options)) {
                $options = array(
                                 array('id' => 0, 'name' => xarML('Submitted')),
                                 array('id' => 1, 'name' => xarML('Rejected')),
                                 array('id' => 2, 'name' => xarML('Approved')),
                                 array('id' => 3, 'name' => xarML('Front Page')),
                           );
            }
            if (empty($value)) {
                $value = 0;
            }
            // fall through to the next one
        case 'select':
        case 'dropdown':
        case 'listbox':
            if (!isset($multiple)) {
                $multiple = '';
            } else {
                $multiple = ' multiple';
            }
            $output .= '<select name="'.$name.'"'.$id.$tabindex.$multiple.'>';
            if (!isset($selected)) {
                if (!empty($value)) {
                    $selected = $value;
                } else {
                    $selected = '';
                }
            }
            if (!isset($options) || !is_array($options)) {
                $options = array();
            }
            foreach ($options as $option) {
                $output .= '<option value="'.$option['id'].'"';
                if ($option['id'] == $selected) {
                    $output .= ' selected';
                }
                $output .= '>'.$option['name'].'</option>';
            }
            $output .= '</select>';
            break;
        case 'file':
        case 'fileupload':
            if (empty($maxsize)) {
                $maxsize = 1000000;
            }
            $output .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.$maxsize.'" />';
            if (empty($size)) {
                $size = 40;
            }
            $output .= '<input type="file" name="'.$name.'" size="'.$size.'"'.$id.$tabindex.' />';
            break;
        case 'url':
            if (empty($size)) {
                $size = 50;
            }
            $output .= '<input type="text" name="'.$name.'" value="'.$value.'" size="'.$size.'"'.$id.$tabindex.' />';
            if (!empty($value)) {
                $output .= ' [ <a href="'.$value.'" target="preview">'.xarML('check').'</a> ]';
            }
            break;
        case 'image':
            if (empty($size)) {
                $size = 50;
            }
            $output .= '<input type="text" name="'.$name.'" value="'.$value.'" size="'.$size.'"'.$id.$tabindex.' />';
            if (!empty($value)) {
                $output .= ' [ <a href="'.$value.'" target="preview">'.xarML('show').'</a> ]';
            }
            $output .= '<br />// TODO: add image picker ?';
            break;
        case 'static':
            $output .= $value;
            break;
        case 'hidden':
            $output .= '<input type="hidden" name="'.$name.'" value="'.$value.'"'.$id.$tabindex.' />';
            break;
        case 'username':
            if (empty($value)) {
                $value = xarUserGetVar('uid');
            }
            $user = xarUserGetVar('name', $value);
            if (empty($user)) {
                $user = xarUserGetVar('uname', $value);
            }
            $output .= $user;
            if ($value > 1) {
                $output .= ' [ <a href="'.xarModURL('users','user','display',
                                                    array('uid' => $value))
                           . '" target="preview">'.xarML('profile').'</a> ]';
            }
            break;
        case 'date':
        case 'calendar':
            if (empty($value)) {
                $value = time();
            }
        // TODO: adapt to local/user time !
            $output .= strftime('%a, %d %B %Y %H:%M:%S %Z', $value);
            $output .= '<br />';
            $localtime = localtime($value,1);
            $output .= xarML('Date') . ' <select name="'.$name.'[year]"'.$id.$tabindex.'>';
            if (empty($minyear)) {
                $minyear = $localtime['tm_year'] + 1900 - 2;
            }
            if (empty($maxyear)) {
                $maxyear = $localtime['tm_year'] + 1900 + 2;
            }
            for ($i = $minyear; $i <= $maxyear; $i++) {
                if ($i == $localtime['tm_year'] + 1900) {
                    $output .= '<option selected>' . $i;
                } else {
                    $output .= '<option>' . $i;
                }
            }
            $output .= '</select> - <select name="'.$name.'[mon]">';
            for ($i = 1; $i <= 12; $i++) {
                if ($i == $localtime['tm_mon'] + 1) {
                    $output .= '<option selected>' . $i;
                } else {
                    $output .= '<option>' . $i;
                }
            }
            $output .= '</select> - <select name="'.$name.'[mday]">';
            for ($i = 1; $i <= 31; $i++) {
                if ($i == $localtime['tm_mday']) {
                    $output .= '<option selected>' . $i;
                } else {
                    $output .= '<option>' . $i;
                }
            }
            $output .= '</select> ';
            $output .= xarML('Time') . ' <select name="'.$name.'[hour]">';
            for ($i = 0; $i < 24; $i++) {
                if ($i == $localtime['tm_hour']) {
                    $output .= '<option selected>' . sprintf("%02d",$i);
                } else {
                    $output .= '<option>' . sprintf("%02d",$i);
                }
            }
            $output .= '</select> : <select name="'.$name.'[min]">';
            for ($i = 0; $i < 60; $i++) {
                if ($i == $localtime['tm_min']) {
                    $output .= '<option selected>' . sprintf("%02d",$i);
                } else {
                    $output .= '<option>' . sprintf("%02d",$i);
                }
            }
            $output .= '</select> : <select name="'.$name.'[sec]">';
            for ($i = 0; $i < 60; $i++) {
                if ($i == $localtime['tm_sec']) {
                    $output .= '<option selected>' . sprintf("%02d",$i);
                } else {
                    $output .= '<option>' . sprintf("%02d",$i);
                }
            }
            $output .= '</select> ';
            break;
        case 'fieldtype':
            $output .= '<select name="'.$name.'"'.$id.$tabindex.'>';
            foreach ($proptypes as $propid => $proptype) {
                $output .= '<option value="'.$propid.'"';
                if ($propid == $value) {
                    $output .= ' selected';
                }
                $output .= '>'.$proptype['label'].'</option>';
            }
            $output .= '</select>';
            break;
        default:
            $output .= 'Unknown type '.xarVarPrepForDisplay($typename);
            break;
    }
    return $output;
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-output ...> form field tags
 * Format : <xar:data-output definition="$definition" /> with $definition an array
 *                                             containing the type, name, value, ...
 *       or <xar:data-output name="thisname" type="thattype" value="$val" ... />
 * 
 * @param $args array containing the input field definition or the type, name, value, ...
 * @returns string
 * @return the PHP code needed to invoke showinput() in the BL template
 */
function dynamicdata_userapi_handleOutputTag($args)
{
    $out = "xarModAPILoad('dynamicdata','user');
echo xarModAPIFunc('dynamicdata',
                   'user',
                   'showoutput',\n";
    if (isset($args['definition'])) {
        $out .= '                   '.$args['definition']."\n";
        $out .= '                  );';
    } else {
        $out .= "                   array(\n";
        foreach ($args as $key => $val) {
            if (is_numeric($val) || substr($val,0,1) == '$') {
                $out .= "                         '$key' => $val,\n";
            } else {
                $out .= "                         '$key' => '$val',\n";
            }
        }
        $out .= "                         ));";
    }
    return $out;
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * show some predefined output field in a template
 * 
 * @param $args array containing the definition of the field (type, name, value, ...)
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_userapi_showoutput($args)
{
    extract($args);
    if (empty($name)) {
        return xarML('Missing \'name\' attribute in field tag or definition');
    }
    if (!isset($type)) {
        $type = 1;
    }
    if (!isset($value)) {
        $value = '';
    }

// TODO: replace with something else
    $proptypes = xarModAPIFunc('dynamicdata','user','getproptypes');
    if (is_numeric($type)) {
        if (!empty($proptypes[$type]['name'])) {
            $typename = $proptypes[$type]['name'];
        } else {
            return xarML('Unknown property type #(1)',$type);
        }
    } else {
        $typename = $type;
    }

    $output = '';
    switch ($typename) {
        case 'text':
        case 'textbox':
            $output .= xarVarPrepHTMLDisplay($value);
            break;
        case 'textarea':
        case 'textarea_small':
        case 'textarea_medium':
        case 'textarea_large':
            $output .= xarVarPrepHTMLDisplay($value);
            break;
    // TEST ONLY
        case 'webpage':
            //$basedir = '/home/mikespub/www/pictures';
            $basedir = 'd:/backup/mikespub/pictures';
            $filetype = 'html?';
            if (!empty($value) &&
                preg_match('/^[a-zA-Z0-9_\/\\\:.-]+$/',$value) &&
                preg_match("/$filetype$/",$value) &&
                file_exists($value) &&
                is_file($value)) {
                $output .= join('', file($value));
            }
                    $output .= xarVarPrepForDisplay($value);
            break;
        case 'status':
            if (!isset($options) || !is_array($options)) {
                $options = array(
                                 array('id' => 0, 'name' => xarML('Submitted')),
                                 array('id' => 1, 'name' => xarML('Rejected')),
                                 array('id' => 2, 'name' => xarML('Approved')),
                                 array('id' => 3, 'name' => xarML('Front Page')),
                           );
            }
            if (empty($value)) {
                $value = 0;
            }
            // fall through to the next one
        case 'select':
        case 'dropdown':
        case 'listbox':
            if (!isset($selected)) {
                if (!empty($value)) {
                    $selected = $value;
                } else {
                    $selected = '';
                }
            }
            if (!isset($options) || !is_array($options)) {
                $options = array();
            }
        // TODO: support multiple selection
            $join = '';
            foreach ($options as $option) {
                if ($option['id'] == $selected) {
                    $output .= $join;
                    $output .= xarVarPrepForDisplay($option['name']);
                    $join = ' | ';
                }
            }
            break;
        case 'file':
        case 'fileupload':
        // TODO: link to download file ?
            break;
        case 'url':
        // TODO: use redirect function here ?
            if (!empty($value)) {
                $value = xarVarPrepHTMLDisplay($value);
        // TODO: add alt/title here ?
                $output .= '<a href="'.$value.'">'.$value.'</a>';
            }
            break;
        case 'image':
            if (!empty($value)) {
                $value = xarVarPrepHTMLDisplay($value);
        // TODO: add size/alt here ?
                $output .= '<img src="' . $value . '">';
            }
            break;
        case 'static':
            $output .= xarVarPrepForDisplay($value);
            break;
        case 'hidden':
            $output .= '';
            break;
        case 'username':
            if (empty($value)) {
                $value = xarUserGetVar('uid');
            }
            $user = xarUserGetVar('name', $value);
            if (empty($user)) {
                $user = xarUserGetVar('uname', $value);
            }
            $output .= $user;
            if ($value > 1) {
                $output .= '<a href="'.xarModURL('users','user','display',
                                                    array('uid' => $value))
                           . '">'.xarVarPrepForDisplay($user).'</a>';
            } else {
                $output .= xarVarPrepForDisplay($user);
            }
            break;
        case 'date':
        case 'calendar':
            if (empty($value)) {
                $value = time();
            }
        // TODO: adapt to local/user time !
            $output .= strftime('%a, %d %B %Y %H:%M:%S %Z', $value);
            break;
        case 'fieldtype':
            if (!empty($value) && !empty($proptypes[$value]['label'])) {
                $output .= $proptypes[$value]['label'];
            }
            break;
        default:
            $output .= 'Unknown type '.xarVarPrepForDisplay($typename);
            break;
    }
    return $output;
}

// ----------------------------------------------------------------------
// TODO: search API, some generic queries for statistics, etc.
//

/**
 * utility function to count the number of items held by this module
 *
 * @author the DynamicData module development team
 * @returns integer
 * @return number of items held by this module
 * @raise DATABASE_ERROR
 */
function dynamicdata_userapi_countitems()
{
    // Get database setup - note that both xarDBGetConn() and xarDBGetTables()
    // return arrays but we handle them differently.  For xarDBGetConn() we
    // currently just want the first item, which is the official database
    // handle.  For xarDBGetTables() we want to keep the entire tables array
    // together for easy reference later on
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    // It's good practice to name the table and column definitions you are
    // getting - $table and $column don't cut it in more complex modules
    $exampletable = $xartable['example'];

    // Get item - the formatting here is not mandatory, but it does make the
    // SQL statement relatively easy to read.  Also, separating out the sql
    // statement from the Execute() command allows for simpler debug operation
    // if it is ever needed
    $sql = "SELECT COUNT(1)
            FROM $exampletable";
    $result = $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an appropriate
    // error message and return
    if ($dbconn->ErrorNo() != 0) {
        // Hint : for debugging SQL queries, you can use $dbconn->ErrorMsg()
        // to retrieve the actual database error message, and use e.g. the
        // following message :
        // $msg = xarML('Database error #(1) in query #(2) for #(3) function ' .
        //             '#(4)() in module #(5)',
        //          $dbconn->ErrorMsg(), $sql, 'user', 'countitems', 'DynamicData');
        // Don't use that for release versions, though...
        /*
        $msg = xarML('Database error for #(1) function #(2)() in module #(3)',
                    'user', 'countitems', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
        */
        // This is the API compliant way to raise a db error exception
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Obtain the number of items
    list($numitems) = $result->fields;

    // All successful database queries produce a result set, and that result
    // set should be closed when it has been finished with
    $result->Close();

    // Return the number of items
    return $numitems;
}

?>
