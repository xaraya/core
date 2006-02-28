<?php
/**
 * Combo Property
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

include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * Handle the combo property
 *
 * @package dynamicdata
 */
class Dynamic_Combo_Property extends Dynamic_Select_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template  = 'combobox';
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id   = 506;
        $info->name = 'combobox';
        $info->desc = 'Combo Dropdown Textbox';

        return $info;
    }

    function checkInput($name = '', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }

        // First check for text in the text box
        $tbname  = $name.'_tb';
        if (!xarVarFetch($tbname, 'isset', $tbvalue,  NULL, XARVAR_DONT_SET)) {return;}

        if( isset($tbvalue) && ($tbvalue != '') )
        {
            $this->fieldname = $tbname;
            $value = $tbvalue;
        } else {
            // Default to checking the selection box.

            // store the fieldname for validations who need them (e.g. file uploads)
            $this->fieldname = $name;
            if (!isset($value))
            {
                if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
            }
        }
        return $this->validateValue($value);
    }

    function validateValue($value = null)
    {
        if (!isset($value))
        {
            $value = $this->value;
        }
        $this->value = $value;

        return true;
    }

    function showOutput($args = array())
    {
        extract($args);
        if (isset($value)) {
            $this->value = $value;
        }
        $data=array();
        $data['value'] = $this->value;
        // get the option corresponding to this value
        $result = $this->getOption();
        $data['option'] = array('id' => $this->value,
                                'name' => xarVarPrepForDisplay($result));

        // If the value wasn't found in the select list data, then it was
        // probably typed in -- so just display it.
        if( !isset($data['option']['name']) || ( $data['option']['name'] == '') )
        {
            $data['option']['name'] = xarVarPrepForDisplay($this->value);
        }

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
