<?php
/**
 * Dynamic URL Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */

include_once "includes/properties/Dynamic_TextBox_Property.php";

class Dynamic_URL_Property extends Dynamic_TextBox_Property
{
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value) && $value != 'http://') {
        // TODO: add some URL validation routine !
            if (preg_match('/[<>"]/',$value)) {
                $this->invalid = xarML('URL');
                $this->value = null;
                return false;
            } else {
                $this->value = $value;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

//    function showInput($name = '', $value = null, $size = 0, $maxlength = 0, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = 'http://';
        }
        return '<input type="text"'.
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '"' .
               ' value="'. (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) . '"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               ' maxlength="'. (!empty($maxlength) ? $maxlength : $this->maxlength) . '"' .
               (!empty($id) ? ' id="'.$id.'"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' />' .
               (!empty($value) && $value != 'http://' ? ' [ <a href="'.$value.'" target="preview">'.xarML('check').'</a> ]' : '') .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    function showOutput($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
    // TODO: use redirect function here ?
        if (!empty($value)) {
            $value = xarVarPrepForDisplay($value);
        // TODO: add alt/title here ?
            return '<a href="'.$value.'">'.$value.'</a>';
        }
        return '';
    }

}

?>