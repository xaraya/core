<?php
/**
 * Dynamic Array Property
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
sys::import('modules.dynamicdata.class.properties');

class Dynamic_Array_Property extends Dynamic_Property
{
    public $id         = 999;
    public $name       = 'array';
    public $desc       = 'Array';
    public $reqmodules = array('base');

    public $fields = array();
    public $size = 40;

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template = 'array';
        $this->filepath   = 'modules/base/xarproperties';

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

    function showInput($data = array())
    {
        if (!isset($data['value'])) $value = $this->value;
        else $value = $data['value'];
        if (isset($data['fields'])) $this->fields = $data['fields'];

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

        $data['value'] = array();
        foreach ($fieldlist as $field) {
            if (!isset($value[$field])) {
                $data['value'][$field] = '';
            } else {
                $data['value'][$field] = xarVarPrepForDisplay($value[$field]);
            }
        }

        $data['size'] = !empty($size) ? $size : $this->size;

        return parent::showInput($data);
    }

    function showOutput($data = array())
    {
        extract($data);
        if (!isset($value)) $value = $this->value;

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

        $data['value'] = array();
        foreach ($fieldlist as $field) {
            if (!isset($value[$field])) {
                $data['value'][$field] = '';
            } else {
                $data['value'][$field] = xarVarPrepForDisplay($value[$field]);
            }
        }
        return parent::showOutput($data);
    }
}
?>
