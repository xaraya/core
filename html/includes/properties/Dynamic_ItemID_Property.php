<?php
/**
 * Dynamic Item ID Property
 *
 * @package dynamicdata
 * @subpackage properties
 */

/**
 * Include the base class
 *
 */
include_once "includes/properties/Dynamic_NumberBox_Property.php";

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
        if (isset($value)) {
            return xarVarPrepForDisplay($value);
        } else {
            return xarVarPrepForDisplay($this->value);
        }
    }

    // default methods from Dynamic_NumberBox_Property
}

?>