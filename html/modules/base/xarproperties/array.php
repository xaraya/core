<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 */
/* include the base class */
sys::import('modules.dynamicdata.class.properties');
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
    public $display_rows = 4;

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
            while (count($keys)) {
                try {
                    $thiskey = array_shift($keys);
                    $thisvalue = array_shift($values);
                    if (!empty($thiskey) && !empty($thisvalue)) 
                        $value[$thiskey] = $thisvalue;
                } catch (Exception $e) {}
            }
        }
        return $this->validateValue($value);;
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
        if (empty($value)) $value = array();
        $this->value = serialize($value);
    }

    public function getValue()
    {
        try {
            $value = unserialize($this->value);
        } catch(Exception $e) {
            $value = null;
        }
        return $value;
    }

    public function showInput(Array $data = array())
    {
        if (!isset($data['value'])) $value = $this->value;
        else $value = $data['value'];
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

        $data['value'] = array();
        foreach ($fieldlist as $field) {
            if (!isset($value[$field])) {
                $data['value'][$field] = '';
            } else {
                $data['value'][$field] = xarVarPrepForDisplay($value[$field]);
            }
        }

        $data['rows'] = !empty($rows) ? $rows : $this->display_rows;
        $data['size'] = !empty($size) ? $size : $this->display_columns;

        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        $value = isset($data['value']) ? $data['value'] : $this->getValue();

        if (!is_array($value)) {
            $data['value'] = $value;
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
                    $data['value'][$field] = xarVarPrepForDisplay($value[$field]);
                }
            }
        }
        return parent::showOutput($data);
    }
}
?>