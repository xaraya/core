<?php
/**
 * Dynamic URL Property
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/*
 * @author mikespub <mikespub@xaraya.com>
*/

/**
 * Include the base class
 *
 */
include_once "modules/base/xarproperties/Dynamic_TextBox_Property.php";

/**
 * handle the URL property
 *
 * @package dynamicdata
 *
 */
class Dynamic_URL_Property extends Dynamic_TextBox_Property
{
    public $requiresmodule = 'base';
    
    public $id     = 11;
    public $name   = 'url';
    public $label  = 'URL';
    public $format = '11';

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
        if (empty($name) || !isset($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id) || !isset($id)) {
            $id = $name;
        }
       $data=array();

        $data['name']     = $name;
        $data['id']       = $id;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['tabindex'] = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['maxlength']= !empty($maxlength) ? $maxlength : $this->maxlength;
        $data['size']     = !empty($size) ? $size : $this->size;

        if (empty($module)) {
            $module = $this->getModule();
        }
        if (empty($template)) {
            $template = $this->getTemplate();
        }

        return xarTplProperty($module, $template, 'showinput', $data);
    }

    function showOutput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }

        $data = array();
        // TODO: use redirect function here ?
        if (!empty($value) && $value != 'http://') {
            $data['value'] = xarVarPrepForDisplay($value);
            //return '<a href="'.$value.'">'.$value.'</a>';

            if (empty($module)) {
                $module = $this->getModule();
            }
            if (empty($template)) {
                $template = $this->getTemplate();
            }

            return xarTplProperty($module, $template, 'showoutput', $data);
        }
        return '';
    }
}
?>
