<?php
/**
 * Multiselect Property
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/*
 * @author mikespub <mikespub@xaraya.com>
*/
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";
/**
 * handle the multiselect property
 *
 * @package dynamicdata
 */
class Dynamic_MultiSelect_Property extends Dynamic_Select_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template =  'multiselect';
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id   = 39;
        $info->name = 'multiselect';
        $info->desc = 'multiselect';

        return $info;
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = array();
        } elseif (!is_array($value)) {
            $tmp = @unserialize($value);
            if ($tmp === false) {
                $value = array($value);
            } else {
                $value = $tmp;
            }
        }
        $validlist = array();
        $options = $this->getOptions();
        foreach ($options as $option) {
            array_push($validlist,$option['id']);
        }
        foreach ($value as $val) {
            if (!in_array($val,$validlist)) {
                $this->invalid = xarML('selection');
                $this->value = null;
                return false;
            }
        }
        $this->value = serialize($value);
        return true;
    }

    function showInput($data = array())
    {
        if (!isset($data['value'])) {
            $data['value'] = $this->value;
        }
        if (!isset($data['options']) || count($data['options']) == 0) {
            $data['options'] = $this->getOptions();
        }
        if (empty($data['value'])) {
            $data['value'] = array();
        } elseif (!is_array($data['value'])) {
            $tmp = @unserialize($data['value']);
            if ($tmp === false) {
                $data['value'] = array($data['value']);
            } else {
                $data['value'] = $tmp;
            }
        }

        $data['single'] = isset($data['single']) ? true : false;

        return parent::showInput($data);
    }

    function showOutput($data = array())
    {
        extract($data);

        if (!isset($value)) $value = $this->value;
        
        if (empty($value)) {
            $value = array();
        } elseif (!is_array($value)) {
            $tmp = @unserialize($value);
            if ($tmp === false) {
                $value = array($value);
            } else {
                $value = $tmp;
            }
        }
        if (!isset($options)) $options = $this->getOptions();
        
        $data['value']= $value;
        $data['options']= $options;

        return parent::showOutput($data);
    }
}
?>
