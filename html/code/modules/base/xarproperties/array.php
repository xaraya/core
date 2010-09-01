<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 */
/* include the base class */
sys::import('modules.dynamicdata.class.properties.base');
/**
 * Handle Array Property
 */
class ArrayProperty extends DataProperty
{
    public $id         = 999;
    public $name       = 'array';
    public $desc       = 'Array';
    public $reqmodules = array('base');

    public $fields = array();

    public $display_columns = 30;
    public $display_columns_count = 1;              // default value of column dimension
    public $display_rows = 4;
    public $initialization_addremove = 0;           
    public $display_key_label = "Key";              // default value of Key label
    public $display_value_label = "Value";          // default value of value label
    public $initialization_associative_array = 0;   // to store the value as associative array
    public $default_suffixlabel = "Row";            // suffix for the Add/Remove Button
    public $initialization_prop_type = 'textbox';   // property type and config for the array values
    public $initialization_prop_config = '';        // TODO: the config is displayed/stored as serialized text for now, to                                                    
                                                    //       avoid nested configs (e.g. see the objects 'config' property)
    public $initialization_fixed_keys = 0;          // allow editing keys on input

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template = 'array';
        $this->filepath   = 'modules/base/xarproperties';
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;

        if (!isset($value)) {
            if (!xarVarFetch($name . '_key', 'array', $keys, array(), XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch($name . '_value',   'array', $values, array(), XARVAR_NOT_REQUIRED)) return;

            //Check for an associative_array.
            if (!xarVarFetch($name . '_associative_array',   'int', $associative_array, null, XARVAR_NOT_REQUIRED)) return;
            //Set value to the initialization_associative_array  
            $this->initialization_associative_array = $associative_array;

            // check if we have a specific property for the values
            if (!xarVarFetch($name . '_has_property', 'isset', $has_property, null, XARVAR_NOT_REQUIRED)) return;
            if (!empty($has_property)) {
                // Note: this relies on the initialized configuration
                $property = $this->getValueProperty();
            }

            if (!empty($property)) {
                $value = array();
                foreach ($keys as $idx => $key) {
                    if (empty($key)) continue;
                    $fieldname = $name . '_value_' . $idx;
                    $isvalid = $property->checkInput($fieldname);
                    if ($isvalid) {
                        $value[$key] = $property->getValue();
                    } else {
                        $this->invalid .= $key . ': ' . $property->invalid;
                    }
                }
            } else {
                $hasvalues = false;
                while (count($keys)) {
                    try {
                        $thiskey = array_shift($keys);
                        $thisvalue = array_shift($values);
                        if (empty($thiskey) && empty($thisvalue)) continue;
                        if ($this->initialization_associative_array && empty($thiskey)) continue;
                        if (is_array($thisvalue) && count($thisvalue) == 1) {
                            $value[$thiskey] = current($thisvalue);
                        } else {
                            $value[$thiskey] = $thisvalue;
                        }
                        $hasvalues = true;
                    } catch (Exception $e) {}
                }
                if (!$hasvalues) $value = array();
            }
        }
        return $this->validateValue($value);
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if (!is_array($value)) {
            $this->value = null;
            return false;
        }
        $this->setValue($value);
        return true;
    }

    function setValue($value=null)
    {
        if (!empty($value) && !is_array($value)) {
            $this->value = $value;
        } else {
        //LEGACY
            if (empty($value)) $value = array();
            //this code is added to store the values as value1,value2 in the DB for non-associative storage
            if(!$this->initialization_associative_array) {
                $elements = "";
                foreach ($value as $element) {
                    if (is_array($element)) {
                        $subelements = "";
                        foreach($element as $subelement){
                            $subelements .= $subelement."%@$#";
                        }
                        $elements .= $subelements.";";
                    } else {
                        $elements .= $element.";";
                    }
                }
                $this->value = $elements;
            } else {
                $this->value = serialize($value);
            }
        }
    }

    public function getValue()
    {
        try {
        // LEGACY
            if(!$this->initialization_associative_array) {
                $outer = explode(';',$this->value);
                $value =array();
                foreach ($outer as $element) {
                    $inner = explode('%@$#',$element);
                    if (count($inner)>1) $value[] = $inner;
                    else $value[] = $element;
                }
            } else {
                $value = unserialize($this->value);
            }
        } catch(Exception $e) {
            $value = null;
        }
        return $value;
    }

    public function showInput(Array $data = array())
    {
        if (!isset($data['value'])) $value = $this->value;
        else $value = $data['value'];
        
        if (!isset($data['suffixlabel'])) $data['suffixlabel'] = $this->default_suffixlabel;
        if (!is_array($value)) {
            try {
                $value = unserialize($value);
                if (!is_array($value)) throw new Exception("Did not find a correct array value");
            } catch (Exception $e) {
                $elements = array();
                $lines = explode(';',$value);
                // remove the last (empty) element
                array_pop($lines);
                foreach ($lines as $element)
                {
                    // allow escaping \, for values that need a comma
                    if (preg_match('/(?<!\\\),/', $element)) {
                        // if the element contains a , we'll assume it's an key,value combination
                        list($key,$name) = preg_split('/(?<!\\\),/', $element);
                        $key = trim(strtr($key,array('\,' => ',')));
                        $val = trim(strtr($val,array('\,' => ',')));
                        $elements[$key] = $val;
                    } else {
                        // otherwise we'll assume no associative array
                        $element = trim(strtr($element,array('\,' => ',')));
                        $element = explode('%@$#',$element); 
                        array_pop($element);                            
                        array_push($elements, $element);
                    }
                }
                $value = $elements;
            }
        }

        // Allow overriding of the field keys from the template
        if (isset($data['fields'])) $this->fields = $data['fields'];
        if (count($this->fields) > 0) {
            $fieldlist = $this->fields;
        } else {
            $fieldlist = array_keys($value);
        }

        // check if we have a specific property for the values
        if (!isset($data['columntype'])) $data['columntype'] = $this->initialization_prop_type;
        if (!isset($data['valueconfig'])) $data['valueconfig'] = $this->initialization_prop_config;
        $data['property'] = $this->getValueProperty($data['columntype'], $data['valueconfig']);

        // use a different default template when dealing with properties
        if (empty($data['template']) && !empty($data['property'])) {
            $data['template'] = 'array_of_props';
        }

        $data['value'] = array();
        foreach ($fieldlist as $field) {
            if (!isset($value[$field])) {
                $data['value'][$field] = '';
            } elseif (is_array($value[$field])) {
                foreach($value[$field] as $k => $v){
                    $data['value'][$field][$k] = xarVarPrepForDisplay($v);
                }
            } else {
                // CHECKME: skip this for array of properties ?
                if (!empty($data['template']) && $data['template'] == 'array_of_props') {
                    $data['value'][$field] = $value[$field];
                } else {
                    $data['value'][$field] = xarVarPrepForDisplay($value[$field]);
                }
            }
        }

        if (!isset($data['rows'])) $data['rows'] = $this->display_rows;
        if (!isset($data['size'])) $data['size'] = $this->display_columns;
        if (!isset($data['columns'])) $data['columns'] = $this->display_columns_count;
        
        if (!isset($data['keylabel'])) $data['keylabel'] = $this->display_key_label;
        if (!isset($data['valuelabel'])) $data['valuelabel'] = $this->display_value_label;
        if (!isset($data['allowinput'])) $data['allowinput'] = $this->initialization_addremove;
        if (!isset($data['associative_array'])) $data['associative_array'] = $this->initialization_associative_array;
        if (!isset($data['fixedkeys'])) $data['fixedkeys'] = $this->initialization_fixed_keys;
        $data['numberofrows'] = count($data['value']);
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (!isset($data['columns'])) $data['columns'] = $this->display_columns_count;
        $value = isset($data['value']) ? $data['value'] : $this->getValue();
        $data['associative_array'] = !empty($associative_array) ? $associative_array : $this->initialization_associative_array;
        if (!is_array($value)) {
            //this is added to show the value with new line when storage is non-associative
            if(!$this->initialization_associative_array) {
                $data['value'] = explode(';',$value);
                // remove the last (empty) element
                 array_pop($data['value']);
            } else {
                 $data['value'] = $value;
            }
        } else {
            if (empty($value)) $value = array();

            if (count($this->fields) > 0) {
                $fieldlist = $this->fields;
            } else {
                $fieldlist = array_keys($value);
            }

            $data['value'] = array();
            foreach ($fieldlist as $field) {
                if (!isset($value[$field])) {
                    $data['value'][$field] = '';
                } else {
                    $data['value'][$field] = $value[$field];
                }
            }
        }

        // check if we have a specific property for the values
        if (!isset($data['valuetype'])) $data['valuetype'] = $this->initialization_prop_type;
        if (!isset($data['valueconfig'])) $data['valueconfig'] = $this->initialization_prop_config;
        $data['property'] = $this->getValueProperty($data['valuetype'], $data['valueconfig']);

        // use a different default template when dealing with properties
        if (empty($data['template']) && !empty($data['property'])) {
            $data['template'] = 'array_of_props';
        }

        return parent::showOutput($data);
    }

    function &getValueProperty($valuetype = '', $valueconfig = '')
    {
        if (empty($valuetype)) {
            $valuetype = $this->initialization_prop_type;
        } else {
            $this->initialization_prop_type = $valuetype;
        }
        if (empty($valueconfig)) {
            $valueconfig = $this->initialization_prop_config;
        } else {
            $this->initialization_prop_config = $valueconfig;
        }
        if (empty($this->initialization_prop_type)) {
            $property = null;
        } elseif ($this->initialization_prop_type == 'textbox' && empty($this->initialization_prop_config)) {
            $property = null;
        } else {
            $property = DataPropertyMaster::getProperty(array('type' => $this->initialization_prop_type));
            if (!empty($this->initialization_prop_config)) {
                $property->parseConfiguration($this->initialization_prop_config);
            }
        }
        return $property;
    }
}
?>
