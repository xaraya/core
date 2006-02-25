<?php
/**
 * Dynamic Item Id property Property
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Include the base class
 *
 */
include_once "modules/base/xarproperties/Dynamic_NumberBox_Property.php";

/**
 * handle item id property
 *
 * @package dynamicdata
 */
class Dynamic_ItemID_Property extends Dynamic_NumberBox_Property
{
    public $requiresmodule = 'dynamicdata';

    public $id     = 21;
    public $name   = 'itemid';
    public $label  = 'Item ID';
    public $format = '21';

    function checkInput($name = '', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
        }
        return $this->validateValue($value);
    }

// TODO: evaluate if we want some other output here
//    function showInput($name = '', $value = null)
    function showInput($args = array())
    {
        extract($args);
        $data = array();

        if (isset($value)) {
            $data['value']= xarVarPrepForDisplay($value);
        } else {
            $data['value']= xarVarPrepForDisplay($this->value);
        }

        // Note: item ids are read-only, even (especially) in input forms

        if (empty($module)) {
            $module = $this->getModule();
        }
        if (empty($template)) {
            $template = $this->getTemplate();
        }

        return xarTplProperty($module, $template, 'showinput', $data);
    }
    // default methods from Dynamic_NumberBox_Property

}

?>
