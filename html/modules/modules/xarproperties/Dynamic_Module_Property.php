<?php
/**
 * Dynamic Data Module Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata properties
 * @author mikespub <mikespub@xaraya.com>
*/

/** 
 * Include the base class
 *
 */
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

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


    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'         => 19,
                              'name'       => 'module',
                              'label'      => 'Module',
                              'format'     => '19',
                              'validation' => '',
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => 'modules',
                            'aliases'        => '',
                            'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }

}

?>
