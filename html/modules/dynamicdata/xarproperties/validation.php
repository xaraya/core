<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.textbox');

/**
 * Handle the validation property
 */
class ValidationProperty extends TextBoxProperty
{
    public $id         = 998;
    public $name       = 'validation';
    public $desc       = 'Validation';
    public $reqmodules = array('dynamicdata');

    public $size      = 50;
    public $maxlength = 254;

    public $proptype = null;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
    }

    public function validateValue($value = null)
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
        $property =& DataPropertyMaster::getProperty($data);

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

    public function showInput(Array $args = array())
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
            $data['type'] = 0; // default DataProperty class
        }
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        // pass the actual id for the property here
        $data['id']         = $this->id;
        // pass the original invalid value here
        $data['invalid']    = !empty($this->invalid) ? $this->invalid :'';
        $property =& DataPropertyMaster::getProperty($data);

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

    public function showOutput(Array $args = array())
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
