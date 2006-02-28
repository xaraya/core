<?php
/**
 * Handle E-mail property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */

/* 
 * Handle E-mail property
 * @author mikespub <mikespub@xaraya.com>
*/

/**
 * Include the base class
 */
include_once "modules/base/xarproperties/Dynamic_TextBox_Property.php";

class Dynamic_Email_Property extends Dynamic_TextBox_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'roles';        
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('roles');
        $info->id   = 26;
        $info->name = 'email';
        $info->desc = 'E-Mail';

        return $info;
    }

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
        $data = array();

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
        $data = array();

        if (!isset($value)) {
            $value = xarVarPrepHTMLDisplay($this->value);
        }
        if (!empty($value)) {
            $value = xarVarPrepHTMLDisplay($value);
        }
        // TODO: use redirect function here ?

        $data['value'] = $value;
        $data['name'] = $this->name;
        $data['id']   = $this->id;

        if (empty($module)) {
            $module = $this->getModule();
        }
        if (empty($template)) {
            $template = $this->getTemplate();
        }

        return xarTplProperty($module, $template, 'showoutput', $data);
    }
}

?>
