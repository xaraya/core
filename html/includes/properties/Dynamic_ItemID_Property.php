<?php
/**
 * File: $Id$
 *
 * Dynamic Item ID Property
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
include_once "modules/base/xarproperties/Dynamic_NumberBox_Property.php";

/**
 * handle item id property
 *
 * @package dynamicdata
 */
class Dynamic_ItemID_Property extends Dynamic_NumberBox_Property
{
// TODO: evaluate if we want some other output here
//    function showInput($name = '', $value = null)
    function showInput($args = array())
    {
        extract($args);
        $data = array();

        if (isset($value)) {
            $data['value']= xarVarPrepForDisplay($value);
        } else {
            $data['value']= xarVarPrepForDisplay($this->value);
        }

        // Note: item ids are read-only, even (especially) in input forms

        $template="itemid";
        return xarTplModule('dynamicdata', 'admin', 'showinput', $data , $template);
    }
    // default methods from Dynamic_NumberBox_Property

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
                              'id'         => 21,
                              'name'       => 'itemid',
                              'label'      => 'Item ID',
                              'format'     => '21',
                              'validation' => '',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => 'dynamicdata',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
}

?>
