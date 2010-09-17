<?php
/**
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @package modules
 * @subpackage roles
 */

sys::import('modules.base.xarproperties.textbox');

/**
 * handle a name property
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */
class NameProperty extends TextBoxProperty
{
    public $id         = 30095;
    public $name       = 'name';
    public $desc       = 'Name';
    public $reqmodules = array('roles');

    public $display_show_salutation;
    public $display_show_firstname;
    public $display_show_middlename;
    public $initialization_refobject    = 'roles_users';    // Name of the object we want to reference

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'roles';
        $this->template =  'name';
        $this->filepath   = 'modules/roles/xarproperties';
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;echo $name;
        if ($this->initialization_refobject == 'roles_groups') {
            $property = DataPropertyMaster::getProperty(array('name' => 'objectref'));
            $property->validation_override = true;
            $property->initialization_refobject = $this->initialization_refobject;
            $property->initialization_store_prop = 'id';
            return $property->checkInput($name, $value);
        } else {
            // store the fieldname for validations who need them (e.g. file uploads)
            $this->fieldname = $name;
            if ($this->display_layout == 'single') {
                $this->display_show_salutation     = 0;
                $this->display_show_firstname      = 0;
                $this->display_show_middlename     = 0;
            }
            if (!isset($value)) {
                $invalid = array();
                $validity = true;
                $value = array();
                $textbox = DataPropertyMaster::getProperty(array('name' => 'textbox'));
                $textbox->validation_min_length = 3;

            $value['salutation'] = '';
            if ($this->display_show_salutation && ($this->display_layout != 'single')) {
                $salutation = DataPropertyMaster::getProperty(array('name' => 'dropdown'));
                $salutation->validation_override = true;
                $isvalid = $salutation->checkInput($name . '_salutation');
                if ($isvalid) {
                    $value['salutation'] = $salutation->value;
                } else {
                    $invalid[] = 'salutation';
                }

                $value['first'] = '';
                if ($this->display_show_firstname && ($this->display_layout != 'single')) {
                    $isvalid = $textbox->checkInput($name . '_first');
                    if ($isvalid) {
                        $value['first'] = $textbox->value;
                    } else {
                        $invalid[] = 'first';
                    }
                    $validity = $validity && $isvalid;
                }

                $value['middle'] = '';
                if ($this->display_show_middlename && ($this->display_layout != 'single')) {
                    $isvalid = $textbox->checkInput($name . '_middle');
                    if ($isvalid) {
                        $value['middle'] = $textbox->value;
                    } else {
                        $invalid[] = 'middle';
                    }
                    $validity = $validity && $isvalid;
                }

                $value['last'] = '';
                $isvalid = $textbox->checkInput($name . '_last');
                if ($isvalid) {
                    $value['last'] = $textbox->value;
                } else {
                    $invalid[] = 'last';
                }
                $validity = $validity && $isvalid;
            }

            if (!empty($invalid)) $this->invalid = implode(',',$invalid);
            $this->value = '%' . $value['last'] .'%' . $value['first'] .'%' . $value['middle'] .'%' . $value['salutation'] .'%';
            return $validity;
        }
    }

    public function showInput(Array $data = array())
    {
        if (empty($data['refobject'])) $data['refobject'] = $this->initialization_refobject;
        if (isset($data['value'])) $this->value = $data['value'];
        $data['value'] = $this->getValueArray();
        return DataProperty::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (empty($data['refobject'])) $data['refobject'] = $this->initialization_refobject;
        if (isset($data['value'])) $this->value = $data['value'];
        $data['value'] = $this->getValueArray();
        return DataProperty::showOutput($data);
    }

    public function getValue()
    {
        $valuearray = $this->getValueArray();
        $value = $valuearray['salutation'] . ' ' . $valuearray['first'] . ' ' . $valuearray['middle'] . ' ' . $valuearray['last'];
        $value = str_replace('  ',' ',$value);
        return trim($value);
    }

    function getValueArray()
    {
        $value = $this->value;
        if (!isset($value)) $value = '%%%%%';
        if (is_array($value)) return $value;
        $value = explode('%', $value);
        
        $valuearray['last'] = !empty($value[1]) ? $value[1] : '';
        $valuearray['first'] = !empty($value[2]) ? $value[2] : '';
        $valuearray['middle'] = !empty($value[3]) ? $value[3] : '';
        $valuearray['salutation'] = !empty($value[4]) ? $value[4] : '';

        // Backward compatibility
        if (!empty($value[0])) $valuearray['last'] = $value[0];
        return $valuearray;
    }
}
?>
