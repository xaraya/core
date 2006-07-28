<?php
/**
 * Combo Property
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/*
 * @author mikespub <mikespub@xaraya.com>
 */

sys::import('modules.base.xarproperties.Dynamic_Select_Property');

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
}
?>
