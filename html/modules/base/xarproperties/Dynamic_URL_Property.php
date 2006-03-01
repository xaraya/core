<?php
/**
 * Dynamic URL Property
 *
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
    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id    = 11;
        $info->name  = 'url';
        $info->desc  = 'URL';

        return $info;
    }

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

    function showInput($data = array())
    {
        if (!isset($data['value'])) {
            $data['value'] = $this->value;
        }
        if (empty($data['value'])) {
            $data['value'] = 'http://';
        }

        return parent::showInput($data);
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
