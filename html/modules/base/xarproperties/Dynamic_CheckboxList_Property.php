<?php
/**
 * Checkbox List Property
 *
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

include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * Class to handle check box list property
 *
 * @package dynamicdata
 */
class Dynamic_CheckboxList_Property extends Dynamic_Select_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->template = 'checkboxlist';
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id   = 1115;
        $info->name = 'checkboxlist';
        $info->desc = 'Checkbox List';

        return $info;
    }

    function checkInput($name='', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            xarVarFetch($name, 'isset', $value,  NULL, XARVAR_NOT_REQUIRED);
        }
        return $this->validateValue($value);
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

        if (empty($module)) {
            $module = $this->getModule();
        }
        if (empty($template)) {
            $template = $this->getTemplate();
        }
        return xarTplProperty($module, $template, 'showinput', $data);
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

        if (empty($module)) {
            $module = $this->getModule();
        }
        if (empty($template)) {
            $template = $this->getTemplate();
        }
        return xarTplProperty($module, $template, 'showoutput', $data);
    }

}

?>
