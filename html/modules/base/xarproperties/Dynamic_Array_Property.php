<?php
/**
 * Dynamic Array Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
include_once "modules/dynamicdata/class/properties.php";
class Dynamic_Array_Property extends Dynamic_Property
{
    var $fields = array();
    var $size = 40;

    function Dynamic_Array_Property($args)
    {
        $this->Dynamic_Property($args);
        // check validation for list of fields (optional)
        if (!empty($this->validation) && strchr($this->validation,';')) {
            $this->fields = explode(';',$this->validation);
        }
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = array('');
        } elseif (!is_array($value)) {
            $out = @unserialize($value);
            if ($out !== false) {
                $value = $out;
            } else {
                $value = array($value);
            }
        }
        if (count($this->fields) > 0) {
        // TODO: do something with field list ?
        }
        $this->value = serialize($value);
        return true;
    }

    function showInput($args = array())
    {
        extract($args);
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        if (!isset($value)) {
            $value = $this->value;
        }
        if (isset($fields)) {
            $this->fields = $fields;
        }
        if (empty($value)) {
            $value = array('');
        } elseif (!is_array($value)) {
            $out = @unserialize($value);
            if ($out !== false) {
                $value = $out;
            } else {
                $value = array($value);
            }
        }
        if (count($this->fields) > 0) {
            $fieldlist = $this->fields;
        } else {
            $fieldlist = array_keys($value);
        }
        $data = array();
        $data['name']     = $name;
        $data['id']       = $id;
        $data['value'] = array();
        foreach ($fieldlist as $field) {
            if (!isset($value[$field])) {
                $data['value'][$field] = '';
            } else {
                $data['value'][$field] = xarVarPrepForDisplay($value[$field]);
            }
        }
        $data['tabindex'] = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['size']     = !empty($size) ? $size : $this->size;

        $template = "";
        return xarTplProperty('base', 'array', 'showinput', $data);
    }

    function showOutput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = array('');
        } elseif (!is_array($value)) {
            $out = @unserialize($value);
            if ($out !== false) {
                $value = $out;
            } else {
                $value = array($value);
            }
        }
        if (count($this->fields) > 0) {
            $fieldlist = $this->fields;
        } else {
            $fieldlist = array_keys($value);
        }
        $data = array();
        $data['value'] = array();
        foreach ($fieldlist as $field) {
            if (!isset($value[$field])) {
                $data['value'][$field] = '';
            } else {
                $data['value'][$field] = xarVarPrepForDisplay($value[$field]);
            }
        }

        $template = "";
        return xarTplProperty('base', 'array', 'showoutput', $data);
    }

    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $baseInfo = array(
                           'id'         => 999,
                           'name'       => 'array',
                           'label'      => 'Array',
                           'format'     => '999',
                           'validation' => '',
                           'source'     => '',
                           'dependancies' => '',
                           'requiresmodule' => '',
                           'aliases' => '',
                           'args'         => '',
                           // ...
                          );
        return $baseInfo;
     }
}

?>
