<?php
/**
 * File: $Id$
 *
 * Dynamic Data URL Icon Property
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
        $data=array();
/*        return '<input type="text"'.
               ' name="' . $name . '"' .
               ' value="'. xarVarPrepForDisplay($value) . '"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               ' maxlength="'. (!empty($maxlength) ? $maxlength : $this->maxlength) . '"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' />' .
               (!empty($value) && $value != 'http://' ? ' [ <a href="'.xarVarPrepForDisplay($value).'" target="preview">'.xarML('check').'</a> ]' : '') .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
*/
        $data['name']     = $name;
        $data['id']       = $id;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['tabindex'] = !empty($tabindex) ? $tabindex=$tabindex : '';
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['maxlength']= !empty($maxlength) ? $maxlength : $this->maxlength;
        $data['size']     = !empty($size) ? $size : $this->size;


        $template="urlicon";
        return xarTplModule('dynamicdata', 'admin', 'showinput', $data , $template);
    }

    function showOutput($args = array())
    {
         extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        $data=array();

    // TODO: use redirect function here ?
        if (!empty($value)) {
            $link = $value;
            $data['link']=xarVarPrepForDisplay($link);
            if (!empty($this->icon)) {
/*                return '<a href="'.xarVarPrepForDisplay($link).'"><img src="'.xarVarPrepForDisplay($this->icon).'" alt="'.xarML('URL').'" /></a>';
*/
                $data['value']= $this->value;
                $data['icon'] = $this->icon;
                $data['name'] = $this->name;
                $data['id']   = $this->id;

                $template="urlicon";
                return xarTplModule('dynamicdata', 'user', 'showoutput', $data ,$template);
            }
        }
        return '';
    }
}

?>
