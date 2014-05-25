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
/**
 * The property is stored as a serialized array of the form
 * array(
 *     'salutation' => [array('id' => 'salutation, 'name' => <salutationvalue>)]  (only one element allowed)
 *     'components' => [array('id' => <fieldname>, 'name' => <field value>)]      (one or more elements allowed)
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
        $value = array('salutation' => array(), 'components' => array());
        $valid = true;

        if (!empty($this->display_salutation_options)) {
            $salutation = DataPropertyMaster::getProperty(array('name' => 'dropdown'));
            $salutation->validation_override = true;
            $isvalid = $salutation->checkInput($name . '_salutation');
            $valid = $valid && $isvalid;
            if ($isvalid) {
                $value['salutation'][] = array('id' => 'salutation', 'name' => $salutation->value);
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
            foreach ($name_components as $field) {
                    $isvalid = $textbox->checkInput($name . '_' . $field['id']);
                $valid = $valid && $isvalid;
                if ($isvalid) {
                    $value['components'][] = array('id' => $field['id'], 'name' => $textbox->value);
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
        if (!empty($valuearray['salutation'])) $value .= ' ' . trim($valuearray['salutation'][0]['name']);
        foreach ($valuearray['components'] as $part) {//var_dump($part);exit;
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
            $value = array('salutation' => array(), 'components' => array(array('id' => 'full_name', 'name' => $this->value)));
        }
        $components = $this->getNameComponents($this->display_name_components);
        $valuearray = array('salutation' => array(), 'components' => array());
        if (!empty($this->display_salutation_options) && !empty($value['salutation'])) {
            $valuearray['salutation'][] = array('id' => 'salutation', 'name' => $value['salutation'][0]['name']);
        }
        foreach ($components as $v) {
            $found = false;
            foreach ($value['components'] as $part) {
                if ($part['id'] == $v['id']) {
                    $valuearray['components'][] = array('id' => $v['id'], 'name' => $part['name']);
                    $found = true;
                    break;
                }
            }
            if (!$found) $valuearray['components'][] = array('id' => $v['id'], 'name' => '');
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
