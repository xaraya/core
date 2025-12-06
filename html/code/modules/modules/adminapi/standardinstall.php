<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminApi;
use BadParameterException;
use sys;

/**
 * modules adminapi standardinstall function
 * @extends MethodClass<AdminApi>
 */
class StandardinstallMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @see AdminApi::standardinstall()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (!isset($module)) {
            return false;
        }

        if (isset($objects)) {
            // FIXME: Data loss risk!!
            $existing_objects  = $this->data()->getObjects();
            foreach ($existing_objects as $objectid => $objectinfo) {
                if (in_array($objectinfo['name'], $objects)) {
                    if (!$this->data()->deleteObject(['objectid' => $objectid])) {
                        return;
                    }
                }
            }
            $dd_objects = [];

            // @todo dont hardcode our naming convention here, nor the path
            foreach ($objects as $dd_object) {
                $name = is_array($dd_object) ? $dd_object['name'] : $dd_object;
                $def_file = sys::code() . 'modules/' . $module . '/xardata/' . $name . '-def.xml';
                $dat_file = sys::code() . 'modules/' . $module . '/xardata/' . $name . '-dat.xml';

                $data = ['file' => $def_file, 'keepitemid' => false];

                // check for $name-def.xml file if available
                if (file_exists($def_file)) {
                    $objectid = $this->mod()->apiFunc('dynamicdata', 'util', 'import', $data);
                    if (!$objectid) {
                        return;
                    } else {
                        $dd_objects[$name] = $objectid;
                    }

                    // Let data import be allowed to be empty
                    if (file_exists($dat_file)) {
                        $data['file'] = $dat_file;
                        // And allow it to fail for now
                        $objectid = $this->mod()->apiFunc('dynamicdata', 'util', 'import', $data);
                    }
                    continue;
                }

                // check for $name-def.php file if available
                if (!file_exists(str_replace('.xml', '.php', $def_file))) {
                    throw new BadParameterException($def_file, 'Invalid importfile "#(1)"');
                }
                $def_file = str_replace('.xml', '.php', $def_file);
                $data = ['file' => $def_file, 'format' => 'php'];
                $objectid = $this->mod()->apiFunc('dynamicdata', 'util', 'import', $data);
                if (!$objectid) {
                    return;
                } else {
                    $dd_objects[$name] = $objectid;
                }

                $dat_file = str_replace('.xml', '.php', $dat_file);
                // Let data import be allowed to be empty
                if (file_exists($dat_file)) {
                    $data['file'] = $dat_file;
                    // And allow it to fail for now
                    $objectid = $this->mod()->apiFunc('dynamicdata', 'util', 'import', $data);
                }
            }
            $this->mod($module)->setVar('dd_objects', serialize($dd_objects));

        } elseif (isset($blocks)) {
            $installed_blocks = [];
            foreach ($blocks as $name) {
                $def_file = sys::code() . 'modules/' . $module . '/xardata/' . $name . '-def.xml';
                $data = ['file' => $def_file];

                $blockid = $this->mod()->apiFunc('blocks', 'admin', 'import', $data);
                if (!$blockid) {
                    return;
                } else {
                    $installed_blocks[$name] = $blockid;
                }
            }
            $this->mod($module)->setVar('blocks', serialize($installed_blocks));

        } else {
            return false;
        }

        return true;
    }
}
