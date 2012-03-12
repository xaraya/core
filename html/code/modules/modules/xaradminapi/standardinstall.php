<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * @param array    $args array of optional parameters<br/>
 */

function modules_adminapi_standardinstall(Array $args=array())
{
    extract($args);
    if (!isset($module)) return false;
    if (!isset($objects)) return false;

    // FIXME: Data loss risk!!
    sys::import('modules.dynamicdata.class.objects.master');
    $existing_objects  = DataObjectMaster::getObjects();
    foreach($existing_objects as $objectid => $objectinfo) {
        if(in_array($objectinfo['name'], $objects)) {
            if(!DataObjectMaster::deleteObject(array('objectid' => $objectid))) return;
        }
    }
    $dd_objects = array();

    // @todo dont hardcode our naming convention here, nor the path 
    foreach($objects as $dd_object) {
        $name = is_array($dd_object) ? $dd_object['name'] : $dd_object;
        $def_file = sys::code() . 'modules/' . $module . '/xardata/'.$name.'-def.xml';
        $dat_file = sys::code() . 'modules/' . $module . '/xardata/'.$name.'-dat.xml';

        $data = array('file' => $def_file, 'keepitemid' => false);
        if (is_array($dd_object)) {
            // pass the args we received though to the import routine
            // (and from there to the class(es) that will use them
            $data = array_merge($data,$dd_object);
        }

        $objectid = xarMod::apiFunc('dynamicdata','util','import', $data);
        if (!$objectid) return;
        else $dd_objects[$name] = $objectid;
        // Let data import be allowed to be empty
        if(file_exists($dat_file)) {
            $data['file'] = $dat_file;
            // And allow it to fail for now
            $objectid = xarMod::apiFunc('dynamicdata','util','import', $data);
        }
    }

    xarModVars::set($module,'dd_objects',serialize($dd_objects));
    return true;
}
?>