<?php

function modules_adminapi_standardinstall($args)
{
    extract($args);
    if (!isset($module)) return false;
    if (!isset($objects)) return false;

    $existing_objects  = xarModAPIFunc('dynamicdata','user','getobjects');
    foreach($existing_objects as $objectid => $objectinfo) {
        if(in_array($objectinfo['name'], $objects)) {
            if(!xarModAPIFunc('dynamicdata','admin','deleteobject', array('objectid' => $objectid))) return;
        }
    }
    $dd_objects = array();

    foreach($objects as $dd_object) {
        $entry = array();
        if (is_array($dd_object)) {
            $entry = $dd_object['entry'];
            $dd_object = $dd_object['name'];
        }
        $def_file = 'modules/' . $module . '/xardata/'.$dd_object.'-def.xml';
        $dat_file = 'modules/' . $module . '/xardata/'.$dd_object.'-dat.xml';
        $objectid = xarModAPIFunc('dynamicdata','util','import', array('file' => $def_file, 'entry' => $entry, 'keepitemid' => false));
        if (!$objectid) return;
        else $dd_objects[$dd_object] = $objectid;
        // Let data import be allowed to be empty
        if(file_exists($dat_file)) {
            // And allow it to fail for now
            xarModAPIFunc('dynamicdata','util','import', array('file' => $dat_file, 'entry' => $entry, 'keepitemid' => false));
        }
    }

    xarModVars::set($module,'dd_objects',serialize($dd_objects));
    return true;
}
?>