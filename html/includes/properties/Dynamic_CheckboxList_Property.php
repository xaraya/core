<?php
/**
 * File: $Id$
 *
 * Dynamic Data Check Box List Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata properties
 * @author mikespub <mikespub@xaraya.com>
*/

include_once "includes/properties/Dynamic_Select_Property.php";

/**
 * Class to handle check box list property
 *
 * @package dynamicdata
 */
class Dynamic_CheckboxList_Property extends Dynamic_Select_Property
{
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
                        'id'         => 1115,
                        'name'       => 'checkboxlist',
                        'label'      => 'Checkbox List',
                        'format'     => '1115',
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
     
     
    function validateValue($value = null)
    {
        // this won't do for check boxes !
        //if (!isset($value)) {
        //    $value = $this->value;
        //}

        if (!isset($value)) {
            $this->value = '';
        } elseif ( is_array($value) ) {
            $this->value = implode ( ',', $value);
        } else {
            $this->value = $value;
        }

        return true;
    }
     
    function showInput($args = array())
    {
        extract($args);
        $data=array();

        if (!isset($value)) 
        {
            $data['value'] = $this->value;
        } else {
            $data['value'] = $value;
        }
        
        if ( empty($data['value']) ) {
            $data['value'] = array();
        } elseif ( !is_array($data['value']) && is_string($data['value']) ) {
            $data['value'] = explode( ',', $data['value'] );
        }
        
        $data['options'] = array();
        if (!isset($options) || count($options) == 0) 
        {
            $options = $this->getOptions();
        }        
        foreach( $options as $key => $option )
        {
            $option['checked'] = in_array($option['id'],$data['value']);
            $data['options'][$key] = $option;
        }
        if (empty($name)) {
            $data['name'] = 'dd_' . $this->id;
        } else {
            $data['name'] = $name;
        }
        if (empty($id)) {
            $data['id'] = $data['name'];
        } else {
            $data['id']= $id;
        }

        $data['tabindex'] =!empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '';
        $data['invalid']  =!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '';


        $template="checkboxlist";
        return xarTplModule('dynamicdata', 'admin', 'showinput', $data ,$template);
    }

    function showOutput($args = array())
    {
        extract($args);
        
        if (!isset($value)) 
        {
            $value = $this->value;
        }
        
        if( is_array($value) )
        {
            $value = implode(',',$value);
        }
        
        $data=array();

        $data['value'] = xarVarPrepForDisplay($value);

        $template="checkboxlist";
        return xarTplModule('dynamicdata', 'user', 'showoutput', $data ,$template);
    }
     
}

?>
