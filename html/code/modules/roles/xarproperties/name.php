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

/**
 * Handle a name property
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */
/**
 * The property is stored as a serialized array of the form
 * array(
 *     [array('id' => <fieldname>, 'name' => <field value>)]      (one or more elements)
 *
 * Default fields displayed are: salutation, first_anme, last_name
 *
 * Note on salutations
 * The property understands the concept of a salutation, and displays any field with the name "salutation" 
 * as a dropdown whose options can be configured in the backend or via an attribute salutation_options in the property tag.
 * When updating, the checkInput method of the textbox property is run on all fields, even salutation.
 * This can be done without issues (we are in any case allowing option overrides) and leaves open the possibility
 * of allowing a template override with the salutation field as a textbox.
 */
 
sys::import('modules.base.xarproperties.textbox');
sys::import('modules.dynamicdata.class.properties.interfaces');

class NameProperty extends TextBoxProperty
{
    public $id         = 30095;
    public $name       = 'name';
    public $desc       = 'Name';
    public $reqmodules = array('roles');

    public $display_name_components = 'salutation,Salutation;first_name,First Name;last_name,Last Name;';
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

        if (!empty($this->display_name_components)) {
            //$salutation = DataPropertyMaster::getProperty(array('name' => 'dropdown'));
            //$salutation->validation_override = true;
            $textbox = DataPropertyMaster::getProperty(array('name' => 'textbox'));
            $name_components = $this->getNameComponents($this->display_name_components);
            if (!$this->validation_ignore_validations) {
                $textbox->validation_min_length = 3;
            }
            foreach ($name_components as $field) {
                $isvalid = $textbox->checkInput($name . '_' . $field['id']);
                $valid = $valid && $isvalid;
                if ($isvalid) {
                    $value[] = array('id' => $field['id'], 'name' => $textbox->value);
                } else {
                    $invalid[] = strtolower($field['name']);
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
        $data['value'] = $this->getValue();
        return DataProperty::showOutput($data);
    }

    public function getValue()
    {
        $valuearray = $this->getValueArray();
        $value = '';
        foreach ($valuearray as $part) {
            try {
                $value .= ' ' . trim($part['name']);
            } catch (Exception $e) {}
        }
        return $value;
    }

    function getValueArray()
    {
        $value = @unserialize($this->value);
        if (!is_array($value)) {
            $value = array((array('id' => 'full_name', 'name' => $this->value)));
        }
        $components = $this->getNameComponents($this->display_name_components);
        foreach ($components as $v) {
            $found = false;
            foreach ($value as $part) {
                if ($part['id'] == $v['id']) {
                    $valuearray[] = array('id' => $v['id'], 'name' => $part['name']);
                    $found = true;
                    break;
                }
            }
            if (!$found) $valuearray[] = array('id' => $v['id'], 'name' => '');
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
                // if the component contains a , we'll assume it's an name/displayname combination
                list($name,$displayname) = preg_split('/(?<!\\\),/', $component);
                $name = trim(strtr($name,array('\,' => ',')));
                $displayname = trim(strtr($displayname,array('\,' => ',')));
                $componentarray[] = array('id' => $name, 'name' => $displayname);
            } else {
                // otherwise we'll use the component for both name and displayname
                $component = trim(strtr($component,array('\,' => ',')));
                $componentarray[] = array('id' => $component, 'name' => $component);
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
