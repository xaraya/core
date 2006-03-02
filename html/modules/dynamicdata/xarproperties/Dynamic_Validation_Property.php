<?php
/**
 * Dynamic Validation property
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
include_once "modules/base/xarproperties/Dynamic_TextBox_Property.php";

/**
 * handle the validation property
 *
 * @package dynamicdata
 */
class Dynamic_Validation_Property extends Dynamic_TextBox_Property
{    
    public $size      = 50;
    public $maxlength = 254;

    public $proptype = null;

    function __construct($args)
    {
        parent::__construct($args);
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('dynamicdata');
        $info->id   = 998;
        $info->name = 'validation';
        $info->desc = 'Validation';

        return $info;
    }

    function validateValue($value = null)
    {
        // get the property type we're currently dealing with
        if (!xarVarIsCached('dynamicdata','currentproptype')) {
            // tell the caller that we don't have a property type
            $this->invalid = xarML('property type');
            // save the value anyway and return true
            if (isset($value)) {
                $this->value = $value;
            }
            return true;
        }
        $proptype = xarVarGetCached('dynamicdata','currentproptype');

        // check if the property type was changed via user input
        $propid = 'dd_' . $proptype->id;
        if (!xarVarFetch($propid,'id',$newtype,NULL,XARVAR_NOT_REQUIRED)) return;

        $data = array();
        // get a new property of the right type
        if (!empty($newtype)) {
            $data['type'] = $newtype;
        } elseif (!empty($proptype->value)) {
            $data['type'] = $proptype->value;
        } else {
            $data['type'] = 0;
        }
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        // pass the actual id for the property here
        $data['id']         = $this->id;
        $property =& Dynamic_Property_Master::getProperty($data);

        // pass the current value as validation rule
        $data['validation'] = isset($value) ? $value : $this->value;

        $isvalid = $property->updateValidation($data);

        if ($isvalid) {
            // store the updated validation rule back in the value and return
            $this->value = $property->validation;
            return true;
        }

        $this->value = null;
        $this->invalid = $property->invalid;
        return false;
    }

    function showInput($args = array())
    {
        extract($args);
        // get the property type we're currently dealing with
        if (!xarVarIsCached('dynamicdata','currentproptype')) {
            // tell the caller that we don't have a property type
            $this->invalid = xarML('property type');
            // let the TextBox property type handle the rest
            return parent::showInput($args);
        }
        $proptype = xarVarGetCached('dynamicdata','currentproptype');

        // check if the property type was changed via user input
        $propid = 'dd_' . $proptype->id;
        if (!xarVarFetch($propid,'id',$newtype,NULL,XARVAR_NOT_REQUIRED)) return;

        $data = array();
        // get a new property of the right type
        if (!empty($newtype)) {
            $data['type'] = $newtype;
        } elseif (!empty($proptype->value)) {
            $data['type'] = $proptype->value;
        } else {
            $data['type'] = 0; // default Dynamic_Property class
        }
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        // pass the actual id for the property here
        $data['id']         = $this->id;
        // pass the original invalid value here
        $data['invalid']    = !empty($this->invalid) ? $this->invalid :'';
        $property =& Dynamic_Property_Master::getProperty($data);

        // pass the id for the input field here
        $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['maxlength']  = !empty($maxlength) ? $maxlength : $this->maxlength;
        $data['size']       = !empty($size) ? $size : $this->size;
        // pass the current value as validation rule
        $data['validation'] = isset($value) ? $value : $this->value;

        // call its showValidation() method and return
        return $property->showValidation($data);
    }

    function showOutput($args = array())
    {
        extract($args);

        if (isset($value)) {
            $value = xarVarPrepHTMLDisplay($value);
        } else {
            $value = xarVarPrepHTMLDisplay($this->value);
        }

        return $value;
    }



}
?>
