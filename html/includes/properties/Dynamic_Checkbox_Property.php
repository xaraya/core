<?php
/**
 * Dynamic Checkbox Property
 *
 * @package dynamicdata
 * @subpackage properties
 */

/**
 * Class to handle check box property
 *
 * @package dynamicdata
 */
class Dynamic_Checkbox_Property extends Dynamic_Property
{
    function validateValue($value = null)
    {
        // this won't do for check boxes !
        //if (!isset($value)) {
        //    $value = $this->value;
        //}
    // TODO: allow different values here, and verify $checked ?
        if (!empty($value)) {
            $this->value = 1;
        } else {
            $this->value = 0;
        }
        return true;
    }

//    function showInput($name = '', $value = null, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        return '<input type="checkbox"'.
               ' name="' . $name . '"' .
               ' value="1"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               (!empty($value) ? ' checked="checked"' : '') .
               ' />' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    function showOutput($args = array())
    {
	    	extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
    // TODO: allow different values here, and verify $checked ?
        if (!empty($value)) {
            return xarML('yes');
        } else {
            return xarML('no');
        }
    }

}

?>
