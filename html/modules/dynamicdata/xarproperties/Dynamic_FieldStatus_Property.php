<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 */
/**
 * Dynamic Data Field Status Property
 * @author mikespub <mikespub@xaraya.com>
*/
sys::import('modules.base.xarproperties.Dynamic_Select_Property');

/**
 * Class to handle field status
 *
 * @package dynamicdata
 */
class Dynamic_FieldStatus_Property extends Dynamic_Select_Property
{
    public $id         = 25;
    public $name       = 'fieldstatus';
    public $desc       = 'Field Status';
    public $reqmodules = array('dynamicdata');

    function __construct($args)
    {
        parent::__construct($args);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
        $this->tplmodule =  'dynamicdata';
        $this->template =  'status';

        if (count($this->options) == 0) {
            $this->options['display'] = array(
                                 array('id' => Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE, 'name' => xarML('Active')),
                                 array('id' => Dynamic_Property_Master::DD_DISPLAYSTATE_DISABLED, 'name' => xarML('Disabled')),
                                 array('id' => Dynamic_Property_Master::DD_DISPLAYSTATE_DISPLAYONLY, 'name' => xarML('Display Only')),
                                 array('id' => Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDEN, 'name' => xarML('Hidden')),
                             );
            $this->options['input'] = array(
                                 array('id' => Dynamic_Property_Master::DD_INPUTSTATE_NOINPUT, 'name' => xarML('No Input Allowed')),
                                 array('id' => Dynamic_Property_Master::DD_INPUTSTATE_ADD, 'name' => xarML('Can be added')),
                                 array('id' => Dynamic_Property_Master::DD_INPUTSTATE_MODIFY, 'name' => xarML('Can be changed')),
                                 array('id' => Dynamic_Property_Master::DD_INPUTSTATE_ADDMODIFY, 'name' => xarML('Can be added/changed')),
                             );
        }
    }

    function showInput($data = array())
    {
        if (!isset($data['value'])) {
            $value = $this->value;
        } else {
            $value = $data['value'];
        }

        $valuearray['display'] = $value & 31;
        $valuearray['input'] = $value & 992;

        $data['value'] = $valuearray;

        if (!isset($data['options']) || count($data['options']) == 0) {
            $data['options'] = $this->getOptions();
        }

        if(!isset($data['onchange'])) $data['onchange'] = null; // let tpl decide what to do
        $data['extraparams'] =!empty($extraparams) ? $extraparams : "";
        return parent::showInput($data);
    }

    function checkInput($name = '', $value = null)
    {
        if (empty($name)) {
            $inputname = 'input_dd_'.$this->id;
            $displayname = 'display_dd_'.$this->id;
        } else {
            $inputname = 'input_'.$name;
            $displayname = 'display_'.$name;
        }
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if(!xarVarFetch($displayname, 'isset', $display_dd_status, NULL, XARVAR_DONT_SET)) {return;}
            if(!xarVarFetch($inputname,   'isset', $input_dd_status,   NULL, XARVAR_DONT_SET)) {return;}
        }
        $value = $display_dd_status + $input_dd_status;
        return $this->validateValue($value);
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE + Dynamic_Property_Master::DD_INPUTSTATE_ADDMODIFY;
        }
        $this->value = $value;
        // Just really check whether we're in bounds. Don't think more is required
        if (($value >= Dynamic_Property_Master::DD_DISPLAYSTATE_DISABLED) &&
            ($value <= Dynamic_Property_Master::DD_INPUTSTATE_MODIFY)) {
            return true;
        }
        return false;
    }

    function getOption($check = false)
    {
        //TODO: get this working
    }
}
?>
