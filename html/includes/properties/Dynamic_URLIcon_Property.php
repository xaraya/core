<?php
/**
 * Dynamic URL Icon Property
 *
 * @package dynamicdata
 * @subpackage properties
 */

/**
 * Include the base class
 *
 */
include_once "includes/properties/Dynamic_TextBox_Property.php";

/**
 * Handle the URLIcon property
 *
 * @package dynamicdata
 */
class Dynamic_URLIcon_Property extends Dynamic_TextBox_Property
{
    var $icon;

    function Dynamic_URLIcon_Property($args)
    {
        $this->Dynamic_Property($args);
        // check validation field for icon to use !
        if (!empty($this->validation)) {
           $this->icon = $this->validation;
        } else {
           $this->icon = xarML('Please specify the icon to use in the validation field');
        }
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value) && $value != 'http://') {
            $this->value = $value;
        } else {
            $this->value = '';
        }
        return true;
    }

    function showInput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = 'http://';
        }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        return '<input type="text"'.
               ' name="' . $name . '"' .
               ' value="'. xarVarPrepForDisplay($value) . '"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               ' maxlength="'. (!empty($maxlength) ? $maxlength : $this->maxlength) . '"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' />' .
               (!empty($value) && $value != 'http://' ? ' [ <a href="'.xarVarPrepForDisplay($value).'" target="preview">'.xarML('check').'</a> ]' : '') .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    function showOutput($args = array())
    {
         extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
    // TODO: use redirect function here ?
        if (!empty($value)) {
            $link = $value;
            if (!empty($this->icon)) {
                return '<a href="'.xarVarPrepForDisplay($link).'"><img src="'.xarVarPrepForDisplay($this->icon).'" alt="'.xarML('URL').'" /></a>';
            }
        }
        return '';
    }
}

?>