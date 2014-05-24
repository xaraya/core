<?php
/**
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */

sys::import('modules.base.xarproperties.textbox');
sys::import('modules.dynamicdata.class.properties.interfaces');

/**
 * Handle a name property
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */
class NameProperty extends TextBoxProperty
{
    public $id         = 30095;
    public $name       = 'name';
    public $desc       = 'Name';
    public $reqmodules = array('roles');

    public $display_name_components = 'first_name,First Name;last_name,Last Name;';
    public $display_salutation_options = 'Mr.,Mrs.,Ms.';
    public $validation_ignore_validations;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'roles';
        $this->template =  'name';
        $this->filepath   = 'modules/roles/xarproperties';
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? $this->propertyprefix . $this->id : $name;
        $invalid = array();
        $value = array();
        $valid = true;

        if (!empty($this->display_salutation_options)) {
            $salutation = DataPropertyMaster::getProperty(array('name' => 'dropdown'));
            $salutation->validation_override = true;
            $isvalid = $salutation->checkInput($name . '_salutation');
            $valid = $valid && $isvalid;
            if ($isvalid) {
                $value['salutation'] = $salutation->value;
            } else {
                $invalid[] = 'salutation';
            }
        }
        
        if (!empty($this->display_name_components)) {
            $name_components = $this->getNameComponents($this->display_name_components);
            $textbox = DataPropertyMaster::getProperty(array('name' => 'textbox'));
            if (!$this->validation_ignore_validations) {
                $textbox->validation_min_length = 3;
            }
            foreach ($name_components as $fieldname => $label) {
                $isvalid = $textbox->checkInput($name . '_' . $fieldname);
                $valid = $valid && $isvalid;
                if ($isvalid) {
                    $value[$fieldname] = $textbox->value;
                } else {
                    $invalid[] = strtolower($label);
                }
            }
            
        }

        if ($valid) {
            $this->value = serialize($value);
        } else {
            $this->value = null;
            $invalid = implode(',',$invalid);
            $this->invalid = xarML('The fields #(1) are not valid', $invalid);
        }
        return $valid;
    }

    public function validateValue($value = null)
    {
        // Dummy method
        xarLog::message("DataProperty::validateValue: Validating property " . $this->name);
        return true;
    }

    public function showInput(Array $data = array())
    {
        if (empty($data['name_components'])) $data['name_components'] = $this->display_name_components;
        else $this->display_name_components = $data['name_components'];
        $data['name_components'] = $this->getNameComponents($data['name_components']);

        if (empty($data['salutation_options'])) $data['salutation_options'] = $this->display_salutation_options;
        else $this->display_salutation_options = $data['salutation_options'];
        $data['salutation_options'] = $this->getSalutationOptions($data['salutation_options']);
        
        if (isset($data['value'])) $this->value = $data['value'];
        $data['value'] = $this->getValueArray();
        return DataProperty::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (empty($data['name_components'])) $data['name_components'] = $this->display_name_components;
        else $this->display_name_components = $data['name_components'];
        $data['name_components'] = $this->getNameComponents($data['name_components']);

        if (empty($data['salutation_options'])) $data['salutation_options'] = $this->display_salutation_options;
        else $this->display_salutation_options = $data['salutation_options'];
        $data['salutation_options'] = $this->getSalutationOptions($data['salutation_options']);
        
        if (isset($data['value'])) $this->value = $data['value'];
        $data['value'] = $this->getValueArray();
        return DataProperty::showOutput($data);
    }

    public function getValue()
    {
        $valuearray = $this->getValueArray();
        $value = implode(' ', $valuearray);
        $value = str_replace('  ',' ',$value);
        return trim($value);
    }

    function getValueArray()
    {
        $value = @unserialize($this->value);
        if (!is_array($value)) $value = array('full_name' => $this->value);
        $components = $this->getNameComponents($this->display_name_components);
        if (!empty($this->display_salutation_options)) $components['salutation'] = xarML('Salutation');
        $valuearray = array();
        foreach ($components as $k => $v) {
            if (isset($value[$k])) $valuearray[$k] = $value[$k];
            else $valuearray[$k] = '';
        }
        return $valuearray;
    }
    
    function getNameComponents($componentstring)
    {
        $components = explode(';', $componentstring);
        // remove the last (empty) element
        array_pop($components);
        $componentarray = array();
        foreach ($components as $component)
        {
            // allow escaping \, for values that need a comma
            if (preg_match('/(?<!\\\),/', $component)) {
                // if the component contains a , we'll assume it's an name/displaynamename combination
                list($name,$displayname) = preg_split('/(?<!\\\),/', $component);
                $name = trim(strtr($name,array('\,' => ',')));
                $displayname = trim(strtr($displayname,array('\,' => ',')));
                $componentarray[$name] = $displayname;
            } else {
                // otherwise we'll use the component for both name and displayname
                $component = trim(strtr($component,array('\,' => ',')));
                $componentarray[$component] = $component;
            }
        }
        return $componentarray;
    }
  
    function getSalutationOptions($string)
    {
        $items = explode(',', $string);
        $optionarray = array();
        foreach ($items as $item) {
            // allow escaping \, for values that need a comma
            $item = trim(strtr($item,array('\,' => ',')));
            $optionarray[] = array('id' => $item, 'name' => $item);
        }
        return $optionarray;
    }
}

class NamePropertyInstall extends NameProperty implements iDataPropertyInstall
{

    public function install(Array $data=array())
    {
        $dat_file = sys::code() . 'modules/roles/xardata/configurations_name-dat.xml';
        $data = array('file' => $dat_file);
        try {
        $objectid = xarMod::apiFunc('dynamicdata','util','import', $data);
        } catch (Exception $e) {}
        return true;
    }
    
}
?>
