<?php
/**
 * Dynamic Module Property
 *
 * @package dynamicdata
 * @subpackage properties
 */

/** 
 * Include the base class
 *
 */
include_once "includes/properties/Dynamic_Select_Property.php";

/**
 * Handle the module property
 *
 * @package dynamicdata
 */
class Dynamic_Module_Property extends Dynamic_Select_Property
{
    function Dynamic_Module_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            $modlist = xarModAPIFunc('modules', 
                             'admin', 
                             'GetList');
            foreach ($modlist as $modinfo) {
                $this->options[] = array('id' => $modinfo['regid'], 'name' => $modinfo['displayname']);
            }
        }
    }

    // default methods from Dynamic_Select_Property
}

?>