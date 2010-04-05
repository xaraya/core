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
        if (!isset($value)) {
            list($isvalid, $lastname) = $this->fetchValue($name . '_last');
            list($isvalid, $firstname) = $this->fetchValue($name . '_first');
            list($isvalid, $middlename) = $this->fetchValue($name . '_middle');
            list($isvalid, $salutation) = $this->fetchValue($name . '_salutation');
        }

        $value = '%' . $lastname .'%' . $firstname .'%' . $middlename .'%' . $salutation .'%';
        return $this->validateValue($value);
    }

    public function showInput(Array $data = array())
    {
        if (empty($data['refobject'])) $data['refobject'] = $this->initialization_refobject;
        if (empty($data['show_salutation'])) $data['show_salutation'] = $this->display_show_salutation;
        if (empty($data['show_middlename'])) $data['show_middlename'] = $this->display_show_middlename;
        if (empty($data['value'])) $data['value'] = $this->value;
        $data['value'] = $this->getvaluearray($data['value']);
        return DataProperty::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (empty($data['refobject'])) $data['refobject'] = $this->initialization_refobject;
        if (empty($data['show_salutation'])) $data['show_salutation'] = $this->display_show_salutation;
        if (empty($data['show_middlename'])) $data['show_middlename'] = $this->display_show_middlename;
        if (empty($data['value'])) $data['value'] = $this->value;
        $data['value'] = $this->getvaluearray($data['value']);
        return DataProperty::showOutput($data);
    }

    public function getValue()
    {
        $valuearray = $this->getvaluearray($this->value);
        $value = $valuearray['salutation'] . ' ' . $valuearray['first'] . ' ' . $valuearray['middle'] . ' ' . $valuearray['last'];
        $value = str_replace('  ',' ',$value);
        return $value;
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