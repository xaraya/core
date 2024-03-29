<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
*/
sys::import('modules.base.xarproperties.dropdown');

/**
 * This property displays a dropdown of dataproperty statuses
 * The allowed values are defined in the dataproperty master class
 * modules/dynamicdata/class/properties/master.php
 */
class FieldStatusProperty extends SelectProperty
{
    public $id         = 25;
    public $name       = 'fieldstatus';
    public $desc       = 'Field Status';
    public $reqmodules = ['dynamicdata'];

    // CHANGEME: make this a configuration?
    public $initialization_display_status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
    public $initialization_input_status   = DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY;

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
        $this->tplmodule  =  'dynamicdata';
        $this->template   =  'fieldstatus';
    }

    /**
    * Display a Dropdown for input
    *
    * @param array<string, mixed> $data An array of input parameters
    * @return string     HTML markup to display the property for input on a web page
    */
    public function showInput(array $data = [])
    {
        if (!isset($data['value'])) {
            $value = $this->value;
        } else {
            $value = $data['value'];
        }

        $valuearray['display'] = $value & DataPropertyMaster::DD_DISPLAYMASK;
        $valuearray['input'] = $value & 992;

        // if the input part is 0 then we need to display default values
        if (empty($valuearray['input'])) {
            $valuearray['display'] = $this->initialization_display_status;
            $valuearray['input'] = $this->initialization_input_status;
        }

        $data['value'] = $valuearray;

        if(!isset($data['onchange'])) {
            $data['onchange'] = null;
        } // let tpl decide what to do
        $data['extraparams'] ??= "";
        return parent::showInput($data);
    }

    /**
    * Get the value of a dropdown from a web page<br/>
    *
    * @param  string $name The name of the dropdown
    * @param  string $value The value of the dropdown
    * @return bool|void   This method passes the value gotten to the validateValue method and returns its output.
    */
    public function checkInput($name = '', $value = null)
    {
        if (empty($name)) {
            $inputname = 'input_' . $this->propertyprefix . $this->id;
            $displayname = 'display_' . $this->propertyprefix . $this->id;
        } else {
            $inputname = 'input_' . $name;
            $displayname = 'display_' . $name;
        }
        // store the fieldname for configurations who need them (e.g. file uploads)
        $this->fieldname = $name;
        $display_status = null;
        $input_status = null;
        if (!isset($value)) {
            if(!xarVar::fetch($displayname, 'isset', $display_status, null, xarVar::DONT_SET)) {
                return;
            }
            if(!xarVar::fetch($inputname, 'isset', $input_status, null, xarVar::DONT_SET)) {
                return;
            }
        }
        $value = $display_status + $input_status;
        return $this->validateValue($value);
    }

    /**
    * Validate the value of a selected dropdown option
    *
    * @return bool Returns true if the value passes all validation checks; otherwise returns false.
    */
    public function validateValue($value = null)
    {
        xarLog::message("DataProperty::validateValue: Validating property " . $this->name, xarLog::LEVEL_DEBUG);

        // FIXME: rework the dataproperty so that the output of getOptions has a correct form
        // and we can call the parent method here
        // if (!parent::validateValue($value)) return false;

        if (empty($value)) {
            $value = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE + DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY;
        }

        // Just really check whether we're in bounds. Don't think more is required
        if (($value >= DataPropertyMaster::DD_DISPLAYSTATE_DISABLED) &&
            ($value <= DataPropertyMaster::DD_INPUTSTATE_MODIFY)) {
            return true;
        }
        return false;
    }

    /**
    * Retrieve the list of options
    *
    */
    public function getOptions()
    {
        $options['display'] = [
                             ['id' => DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE, 'name' => xarML('All Views')],
                             ['id' => DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY, 'name' => xarML('List only')],
                             ['id' => DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY, 'name' => xarML('Display only')],
                             ['id' => DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN, 'name' => xarML('Hidden')],
                             ['id' => DataPropertyMaster::DD_DISPLAYSTATE_DISABLED, 'name' => xarML('Disabled')],
                         ];
        $options['input'] = [
                             ['id' => DataPropertyMaster::DD_INPUTSTATE_IGNORED, 'name' => xarML('Ignored for input')],
                             ['id' => DataPropertyMaster::DD_INPUTSTATE_NOINPUT, 'name' => xarML('No manual input')],
                             ['id' => DataPropertyMaster::DD_INPUTSTATE_ADD, 'name' => xarML('Can be added')],
                             ['id' => DataPropertyMaster::DD_INPUTSTATE_MODIFY, 'name' => xarML('Can be changed')],
                             ['id' => DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY, 'name' => xarML('Can be added/changed')],
                         ];
        return $options;
    }


    public function getOption($check = false)
    {
        //TODO: get this working
    }
}
