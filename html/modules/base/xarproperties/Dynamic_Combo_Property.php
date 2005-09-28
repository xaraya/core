<?php
/**
 * File: $Id$
 *
 * Dynamic Data Combo Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata properties
 * @author mikespub <mikespub@xaraya.com>
*/

include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * Handle the combo property
 *
 * @package dynamicdata
 */
class Dynamic_Combo_Property extends Dynamic_Select_Property
{

    function checkInput($name = '', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }

        // First check for text in the text box
        $tbname  = $name.'_tb';
        if (!xarVarFetch($tbname, 'isset', $tbvalue,  NULL, XARVAR_DONT_SET)) {return;}

        if( isset($tbvalue) && ($tbvalue != '') )
        {
            $this->fieldname = $tbname;
            $value = $tbvalue;
        } else {
            // Default to checking the selection box.

            // store the fieldname for validations who need them (e.g. file uploads)
            $this->fieldname = $name;
            if (!isset($value)) 
            {
                if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
            }
        }
        return $this->validateValue($value);
    }

    function validateValue($value = null)
    {
        if (!isset($value)) 
        {
            $value = $this->value;
        }
        $this->value = $value;
        
        return true;
    }

//    function showInput($name = '', $value = null, $options = array(), $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        
        $data=array();

        if (!isset($value)) {
            $data['value'] = $this->value;
        } else {
            $data['value'] = $value;
        }
        
        if (!isset($options) || count($options) == 0) {
            $data['options'] = $this->getOptions();
        } else {
            $data['options'] = $options;
        }
        if (empty($name)) {
            $data['name'] = 'dd_' . $this->id;
        } else {
            $data['name'] = $name;
        }
        if (empty($id)) 
        {
            $data['id'] = $data['name'];
        } else {
            $data['id']= $id;
        }

        $data['tabindex'] =!empty($tabindex) ? $tabindex : 0;
        $data['invalid']  =!empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';

        $template="";
        return xarTplProperty('base', 'combobox', 'showinput', $data);
    }

    function showOutput($args = array())
    {
        extract($args);
        if (isset($value)) {
            $this->value = $value;
        }
        $data=array();
        $data['value'] = $this->value;
        // get the option corresponding to this value
        $result = $this->getOption();
        $data['option'] = array('id' => $this->value,
                                'name' => xarVarPrepForDisplay($result));

        // If the value wasn't found in the select list data, then it was
        // probably typed in -- so just display it.
        if( !isset($data['option']['name']) || ( $data['option']['name'] == '') )
        {
            $data['option']['name'] = xarVarPrepForDisplay($this->value);
        }

        $template="";
        return xarTplProperty('base', 'combobox', 'showoutput', $data);
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
                              'id'         => 506,
                              'name'       => 'combo',
                              'label'      => 'Combo Dropdown Textbox',
                              'format'     => '506',
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
