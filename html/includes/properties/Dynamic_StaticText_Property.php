<?php
/**
 * Dynamic Static Text Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_StaticText_Property extends Dynamic_Property
{
    function validateValue($value = null)
    {
        if (isset($value) && $value != $this->value) {
            $this->invalid = xarML('static text');
            $this->value = null;
            return false;
        }
        return true;
    }

//    function showInput($name = '', $value = null, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        return (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    // default showOutput() from Dynamic_Property
}

?>