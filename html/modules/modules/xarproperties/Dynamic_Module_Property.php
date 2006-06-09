<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 * @author mikespub
 */

/**
 * Dynamic Data Module Property
 * @author mikespub
 * Include the base class
 */
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * Handle the module property
 *
 * @package dynamicdata
 */
class Dynamic_Module_Property extends Dynamic_Select_Property
{
    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('modules');
        $info->id   = 19;
        $info->name = 'module';
        $info->desc = 'Module';

        return $info;
    }

    function getOptions()
    {
        if (count($this->options) == 0) {
            // TODO: wasnt here an $args earlier? where did this go?
            $modlist = xarModAPIFunc('modules', 'admin', 'getlist');
            foreach ($modlist as $modinfo) {
                $this->options[] = array('id' => $modinfo['regid'], 'name' => $modinfo['displayname']);
            }
        }
        return $this->options;
    }
}

?>
