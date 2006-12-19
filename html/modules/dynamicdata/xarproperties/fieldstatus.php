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
sys::import('modules.base.xarproperties.dropdown');

/**
 * Handle field status property
 */
class FieldStatusProperty extends SelectProperty
{
    public $id         = 25;
    public $name       = 'fieldstatus';
    public $desc       = 'Field Status';
    public $reqmodules = array('dynamicdata');

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
        $this->tplmodule =  'dynamicdata';
        $this->template =  'status';

        if (count($this->options) == 0) {
            $this->options['display'] = array(
                                 array('id' => DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE, 'name' => xarML('Active')),
                                 array('id' => DataPropertyMaster::DD_DISPLAYSTATE_DISABLED, 'name' => xarML('Disabled')),
                                 array('id' => DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY, 'name' => xarML('Display only')),
                                 array('id' => DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN, 'name' => xarML('Hidden')),
                             );
            $this->options['input'] = array(
                                 array('id' => DataPropertyMaster::DD_INPUTSTATE_NOINPUT, 'name' => xarML('No input allowed')),
                                 array('id' => DataPropertyMaster::DD_INPUTSTATE_ADD, 'name' => xarML('Can be added')),
                                 array('id' => DataPropertyMaster::DD_INPUTSTATE_MODIFY, 'name' => xarML('Can be changed')),
                                 array('id' => DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY, 'name' => xarML('Can be added/changed')),
                             );
        }
    }

    public function showInput(Array $data = array())
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

    public function checkInput($name = '', $value = null)
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

    public function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE + DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY;
        }
        $this->value = $value;
        // Just really check whether we're in bounds. Don't think more is required
        if (($value >= DataPropertyMaster::DD_DISPLAYSTATE_DISABLED) &&
            ($value <= DataPropertyMaster::DD_INPUTSTATE_MODIFY)) {
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
