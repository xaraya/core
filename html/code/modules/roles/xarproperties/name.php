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

    public $display_show_salutation     = true;
    public $display_show_firstname      = true;
    public $display_show_middlename     = true;
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
        $name = empty($name) ? 'dd_'.$this->id : $name;
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if ($this->display_layout == 'single') {
            $this->display_show_salutation     = false;
            $this->display_show_firstname      = false;
            $this->display_show_middlename     = false;
        }
        if (!isset($value)) {
            $invalid = array();
            $validity = true;
            $value = array();
            $textbox = DataPropertyMaster::getProperty(array('name' => 'textbox'));
            $textbox->validation_min_length = 3;

            $value['salutation'] = '';
            if ($this->display_show_salutation) {
                $salutation = DataPropertyMaster::getProperty(array('name' => 'dropdown'));
                $isvalid = $salutation->checkInput($name . '_salutation');
                if ($isvalid) {
                    $value['salutation'] = $salutation->value;
                } else {
                    $invalid[] = 'salutation';
                }
                $validity = $validity && $isvalid;
            }
            
            $value['first_name'] = '';
            if ($this->display_show_firstname) {
                $isvalid = $textbox->checkInput($name . '_first');
                if ($isvalid) {
                    $value['first_name'] = $textbox->value;
                } else {
                    $invalid[] = 'first_name';
                }
                $validity = $validity && $isvalid;
            }

            $value['middle_name'] = '';
            if ($this->display_show_middlename) {
                $isvalid = $textbox->checkInput($name . '_middle');
                if ($isvalid) {
                    $value['middle_name'] = $textbox->value;
                } else {
                    $invalid[] = 'middle_name';
                }
                $validity = $validity && $isvalid;
            }

            $value['last_name'] = '';
            $isvalid = $textbox->checkInput($name . '_last');
            if ($isvalid) {
                $value['last_name'] = $textbox->value;
            } else {
                $invalid[] = 'last_name';
            }
            $validity = $validity && $isvalid;
        }

        if (!empty($invalid)) $this->invalid = implode(',',$invalid);
        $this->value = '%' . $value['last_name'] .'%' . $value['first_name'] .'%' . $value['middle_name'] .'%' . $value['salutation'] .'%';
        return $validity;
    }

    public function showInput(Array $data = array())
    {
        if (empty($data['refobject'])) $data['refobject'] = $this->initialization_refobject;
        if (!isset($data['show_salutation'])) $data['show_salutation'] = $this->display_show_salutation;
        if (!isset($data['show_firstname'])) $data['show_firstname'] = $this->display_show_firstname;
        if (!isset($data['show_middlename'])) $data['show_middlename'] = $this->display_show_middlename;
        if (empty($data['value'])) $data['value'] = $this->value;
        $data['value'] = $this->getvaluearray($data['value']);
        return DataProperty::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (empty($data['refobject'])) $data['refobject'] = $this->initialization_refobject;
        if (!isset($data['show_salutation'])) $data['show_salutation'] = $this->display_show_salutation;
        if (!isset($data['show_firstename'])) $data['show_firstename'] = $this->display_show_firstname;
        if (!isset($data['show_middlename'])) $data['show_middlename'] = $this->display_show_middlename;
        if (empty($data['value'])) $data['value'] = $this->value;
        $data['value'] = $this->getvaluearray($data['value']);
        return DataProperty::showOutput($data);
    }

    public function getValue()
    {
        $valuearray = $this->getvaluearray($this->value);
        $value = $valuearray['salutation'] . ' ' . $valuearray['first'] . ' ' . $valuearray['middle'] . ' ' . $valuearray['last'];
        $value = str_replace('  ',' ',$value);
        return trim($value);
    }

    function getvaluearray($value)
    {
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