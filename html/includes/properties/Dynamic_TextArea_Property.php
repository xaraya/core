<?php
/**
 * Dynamic Text Area Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_TextArea_Property extends Dynamic_Property
{
    var $rows = 8;
    var $cols = 50;
    var $wrap = 'soft';

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
    // TODO: allowable HTML ?
        $this->value = $value;
        return true;
    }

//    function showInput($name = '', $value = null, $rows = 8, $cols = 50, $wrap = 'soft', $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        return '<textarea' .
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '"' .
               ' rows="'. (!empty($rows) ? $rows : $this->rows) . '"' .
               ' cols="'. (!empty($cols) ? $cols : $this->cols) . '"' .
               ' wrap="'. (!empty($wrap) ? $wrap : $this->wrap) . '"' .
               (!empty($id) ? ' id="'.$id.'"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               '>' . (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) . '</textarea>' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    function showOutput($value = null)
    {
        if (isset($value)) {
            return xarVarPrepHTMLDisplay($value);
        } else {
            return xarVarPrepHTMLDisplay($this->value);
        }
    }

}


?>