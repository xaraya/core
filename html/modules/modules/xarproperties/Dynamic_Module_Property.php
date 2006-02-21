<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
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
    public $id = 19;
    public $name = 'module';
    public $label = 'Module';
    public $format = '19';
    public $requiresmodule = 'modules';

    function Dynamic_Module_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            $modlist = xarModAPIFunc('modules',
                             'admin',
                             'getlist',$args);
            foreach ($modlist as $modinfo) {
                $this->options[] = array('id' => $modinfo['regid'], 'name' => $modinfo['displayname']);
            }
        }
    }
}

?>
