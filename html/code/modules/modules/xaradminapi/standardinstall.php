<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */
/**
 * @param array<string, mixed> $args array of optional parameters<br/>
 */

function modules_adminapi_standardinstall(Array $args=array())
{
    extract($args);
    if (!isset($module)) return false;

    if (isset($objects)) {
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
            // @deprecated 2.4.0 no additional object arguments supported since Jamaica
            if (is_array($dd_object)) {
                // pass the args we received though to the import routine
                // (and from there to the class(es) that will use them
                $data = array_merge($data,$dd_object);
            }

            // check for $name-def.xml file if available
            if (file_exists($def_file)) {
                $objectid = xarMod::apiFunc('dynamicdata','util','import', $data);
                if (!$objectid) return;
                else $dd_objects[$name] = $objectid;
            
                // Let data import be allowed to be empty
                if (file_exists($dat_file)) {
                    $data['file'] = $dat_file;
                    // And allow it to fail for now
                    $objectid = xarMod::apiFunc('dynamicdata','util','import', $data);
                }
                continue;
            }

            // check for $name-def.php file if available
            if (!file_exists(str_replace('.xml', '.php', $def_file))) {
                throw new BadParameterException($def_file, 'Invalid importfile "#(1)"');
            }
            $def_file = str_replace('.xml', '.php', $def_file);
            $data = ['file' => $def_file, 'format' => 'php'];
            $objectid = xarMod::apiFunc('dynamicdata','util','import', $data);
            if (!$objectid) return;
            else $dd_objects[$name] = $objectid;

            $dat_file = str_replace('.xml', '.php', $dat_file);
            // Let data import be allowed to be empty
            if (file_exists($dat_file)) {
                $data['file'] = $dat_file;
                // And allow it to fail for now
                $objectid = xarMod::apiFunc('dynamicdata','util','import', $data);
            }
        }
        xarModVars::set($module,'dd_objects',serialize($dd_objects));

    } elseif (isset($blocks)) {
        $installed_blocks = array();
        foreach($blocks as $name) {
            $def_file = sys::code() . 'modules/' . $module . '/xardata/'.$name.'-def.xml';
            $data = array('file' => $def_file);

            $blockid = xarMod::apiFunc('blocks','admin','import', $data);
            if (!$blockid) return;
            else $installed_blocks[$name] = $blockid;
        }
        xarModVars::set($module,'blocks',serialize($installed_blocks));

    } else {
        return false;
    }

    return true;
}
