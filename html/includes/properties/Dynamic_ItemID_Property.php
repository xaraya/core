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
        $data = array();

        if (isset($value)) {
            $data['value']= xarVarPrepForDisplay($value);
        } else {
            $data['value']= xarVarPrepForDisplay($this->value);
        }

        $data['name']    = isset($name) ? xarVarPrepForDisplay($name) : xarVarPrepForDisplay($this->name);
        $data['id']    = isset($id) ? xarVarPrepForDisplay($id) : xarVarPrepForDisplay($this->id);
        $data['tabindex'] = !empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['maxlength']= !empty($maxlength) ? $maxlength : $this->maxlength;
        $data['size']     = !empty($size) ? $size : $this->size;

    $template="itemid";
    return xarTplModule('dynamicdata', 'admin', 'showinput', $data , $template);
    }
    // default methods from Dynamic_NumberBox_Property
}

?>
