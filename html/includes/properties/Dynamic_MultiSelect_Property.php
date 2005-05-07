<?php
/**
 * File: $Id$
 *
 * Dynamic Multi Select Property (saved as serialized array internally)
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata properties
 * @author mikespub <mikespub@xaraya.com>
*/

/**
 * Include the base class
 *
 */
include_once "includes/properties/Dynamic_Select_Property.php";

/**
 * handle the multiselect property
 *
 * @package dynamicdata
 */
class Dynamic_MultiSelect_Property extends Dynamic_Select_Property
{
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

//    function showInput($name = '', $value = null, $options = array(), $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        $data = array();

        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($options) || count($options) == 0) {
            $options = $this->getOptions();
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
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        $data['value']  = $value;
        $data['name']   = $name;
        $data['id']     = $id;
        $data['options']= $options;

        $data['tabindex'] =!empty($tabindex) ? $tabindex : 0;
        $data['invalid']  =!empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';

        $template="multiselect";
        return xarTplModule('dynamicdata', 'admin', 'showinput', $data ,$template);
    }

    function showOutput($args = array())
    {
        extract($args);
        $data = array();

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
        if (!isset($options)) {
            $options = $this->getOptions();
        }
        $data['value']= $value;
        $data['options']= $options;

        $template="multiselect";
        return xarTplModule('dynamicdata', 'user', 'showoutput', $data ,$template);
    }


    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'         => 39,
                              'name'       => 'multiselect',
                              'label'      => 'Multi Select',
                              'format'     => '39',
                              'validation' => '',
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => '',
                            'aliases'        => '',
                            'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }


}


?>
