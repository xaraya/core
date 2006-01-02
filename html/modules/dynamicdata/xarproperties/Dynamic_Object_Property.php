<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Include the base class
 *
 */
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * handle the object property
 *
 * @package dynamicdata
 */
/*
* Options available to user selection
* ===================================
* Options take the form:
*   option-type:option-value;
* option-types:
*   static:true - add modules to the list
*/

class Dynamic_Object_Property extends Dynamic_Select_Property
{
    function Dynamic_Object_Property($args)
    {
        $this->Dynamic_Select_Property($args);

        if (!empty($this->validation)) {
            foreach(preg_split('/(?<!\\\);/', $this->validation) as $option) {
                // Semi-colons can be escaped with a '\' prefix.
                $option = str_replace('\;', ';', $option);
                // An option comes in two parts: option-type:option-value
                if (strchr($option, ':')) {
                    list($option_type, $option_value) = explode(':', $option, 2);
                    if ($option_type == 'static' && $option_value == 1) {
                        $includestatics = true;
                        $modlist = xarModAPIFunc('modules',
                                         'admin',
                                         'GetList');
                        foreach ($modlist as $modinfo) {
                            $this->options[] = array('id' => $modinfo['regid'], 'name' => $modinfo['displayname']);
                        }
                    }
                }
            }
        }
//        if (count($this->options) == 0) {
            $objects =& Dynamic_Object_Master::getObjects();
            if (!isset($objects)) {
                $objects = array();
            }
            foreach ($objects as $objectid => $object) {
                if (!empty($includestatics)) {
                    $ancestors = xarModAPIFunc('dynamicdata','user','getancestors',array('objectid' => $objectid, 'top' => false));
                    $name ="";
                    foreach ($ancestors as $parent) $name .= $parent['name'] . ".";
                    $this->options[] = array('id' => '182.' . $objectid, 'name' => $name . $object['name']);
                } else {
                    $this->options[] = array('id' => $objectid, 'name' => $object['name']);
                }
            }
//        }
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
                              'id'         => 24,
                              'name'       => 'object',
                              'label'      => 'Object',
                              'format'     => '24',
                              'validation' => '',
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => 'dynamicdata',
                            'aliases'        => '',
                            'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }

}

?>