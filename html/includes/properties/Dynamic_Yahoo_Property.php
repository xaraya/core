<?php
/**
 * Dynamic Yahoo Messenger Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */

include_once "includes/properties/Dynamic_URLIcon_Property.php";

class Dynamic_Yahoo_Property extends Dynamic_URLIcon_Property
{
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            if (preg_match('/^[a-z0-9_-]+$/i',$value)) { // TODO: refine this !?
                $this->value = $value;
            } else {
                $this->invalid = xarML('Yahoo Messenger');
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
        if (!empty($value)) {
            $link = 'http://edit.yahoo.com/config/send_webmesg?.target='.$value.'&.src=pg';
        } else {
            $link = '';
        }
        return '<input type="text"'.
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '"' .
               ' value="'. (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) . '"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               ' maxlength="'. (!empty($maxlength) ? $maxlength : $this->maxlength) . '"' .
               (!empty($id) ? ' id="'.$id.'"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' />' .
               (!empty($link) ? ' [ <a href="'.xarVarPrepForDisplay($link).'" target="preview">'.xarML('check').'</a> ]' : '') .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    function showOutput($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            $link = 'http://edit.yahoo.com/config/send_webmesg?.target='.$value.'&.src=pg';
            if (!empty($this->icon)) {
                return '<a href="'.xarVarPrepForDisplay($link).'"><img src="'.xarVarPrepForDisplay($this->icon).'" /></a>';
            }
        }
        return '';
    }
}

?>