<?php
/**
 * Dynamic E-Mail Property
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
 * Class to handle E-mail property
 *
 * @package dynamicdata
 */
class Dynamic_Email_Property extends Dynamic_TextBox_Property
{
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            // cfr. pnVarValidate in pnLegacy.php
            $regexp = '/^(?:[^\s\000-\037\177\(\)<>@,;:\\"\[\]]\.?)+@(?:[^\s\000-\037\177\(\)<>@,;:\\\"\[\]]\.?)+\.[a-z]{2,6}$/Ui';
            if (preg_match($regexp,$value)) {
                $this->value = $value;
            } else {
                $this->invalid = xarML('E-Mail');
                $this->value = null;
                return false;
            }
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
            $value = '';
        }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        return '<input type="text"'.
               ' name="' . $name . '"' .
               ' value="'. (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) . '"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               ' maxlength="'. (!empty($maxlength) ? $maxlength : $this->maxlength) . '"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' />' .
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
            $value = xarVarPrepForDisplay($value);
            return '<a href="mailto:'.$value.'">'.$value.'</a>';
        }
        return '';
    }

}

?>
