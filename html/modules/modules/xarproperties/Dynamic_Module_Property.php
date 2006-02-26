<?php
/**
 * Dynamic Data Module Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 * @author mikespub
 */

/**
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
    function __construct($args)
    {
        parent::__construct($args);
        $this->id = 19;
        $this->name = 'module';
        $this->label = 'Module';
        $this->format = '19';
        $this->requiresmodule = 'modules';
    }
    
    function getOptions()
    {
        if (count($this->options) == 0) {
            $modlist = xarModAPIFunc('modules', 'admin', 'getlist',$args);
            foreach ($modlist as $modinfo) {
                $this->options[] = array('id' => $modinfo['regid'], 'name' => $modinfo['displayname']);
            }
        }    
        return $this->options;
    }
}

?>