<?php
/**
 * Dynamic Hidden Property
 *
 * @package dynamicdata
 * @subpackage properties
 */

/**
 * Class to handle hidden properties
 *
 * @package dynamicdata
 */
class Dynamic_Hidden_Property extends Dynamic_Property
{
    function validateValue($value = null)
    {
        if (isset($value) && $value != $this->value) {
            $this->invalid = xarML('hidden field');
            $this->value = null;
            return false;
        } else {
            return true;
        }
    }

//    function showInput($name = '', $value = null)
    function showInput($args = array())
    {
        extract($args);
        return '<input type="hidden"'.
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '"' .
               ' value="'. (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) . '"' .
               ' />' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    function showOutput($args = array())
    {
	    	extract($args);
        return '';
    }

}

?>