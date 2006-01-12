<?php
/**
 * Checkbox Property
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/*
 * @author mikespub <mikespub@xaraya.com>
 */
/* Include parent class  */
include_once "modules/dynamicdata/class/properties.php";
/**
 * Class to handle check box property
 *
 * @package dynamicdata
 */
class Dynamic_Checkbox_Property extends Dynamic_Property
{
    function checkInput($name='', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
        }
        return $this->validateValue($value);
    }

    function validateValue($value = null)
    {
        // this won't do for check boxes !
        //if (!isset($value)) {
        //    $value = $this->value;
        //}
    // TODO: allow different values here, and verify $checked ?
        if (!empty($value)) {
            $this->value = 1;
        } else {
            $this->value = 0;
        }
        return true;
    }

//    function showInput($name = '', $value = null, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);

        $data=array();

        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        $data['value']=$value;
        $data['name']=$name;
        $data['id']=$id;
        $data['checked'] = isset($checked) && $checked ? true : false;
        $data['onchange'] = !empty($onchange) ? $onchange : null; // let tpl decide what to do with it
        $data['tabindex']=!empty($tabindex) ? $tabindex : 0;
        $data['invalid'] = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid): '';

        $template="";
        return xarTplProperty('base', 'checkbox', 'showinput', $data);

    }

    function showOutput($args = array())
    {
        extract($args);

        $data=array();
        if (!isset($value)) {
            $value = $this->value;
        }
        $data['value']=$value;
        // TODO: allow different values here, and verify $checked ?
        //Move ML language defines to templates
        /*if (!empty($value)) {
            return xarML('yes');
        } else {
            return xarML('no');
        }*/
        $template="";
        return xarTplProperty('base', 'checkbox', 'showoutput', $data);

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
                              'id'         => 14,
                              'name'       => 'checkbox',
                              'label'      => 'Checkbox',
                              'format'     => '14',
                              'validation' => '',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => '',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
}

?>
