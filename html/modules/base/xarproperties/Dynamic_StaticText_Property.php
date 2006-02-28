<?php
/**
 * Dynamic Static Text property
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
class Dynamic_StaticText_Property extends Dynamic_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template = 'static';
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id   = 1;
        $info->name = 'static';
        $info->desc = 'Static Text';

        return $info;
    }

    function validateValue($value = null)
    {
        if (isset($value) && $value != $this->value) {
            $this->invalid = xarML('static text');
            $this->value = null;
            return false;
        }
        return true;
    }

//    function showInput($name = '', $value = null, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);

        $data=array();

        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        $data['name']     = $name;
        $data['id']       = $id;


        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        if (empty($module)) {
            $module = $this->getModule();
        }
        if (empty($template)) {
            $template = $this->getTemplate();
        }

        return xarTplProperty($module, $template, 'showinput', $data);
    }

}
?>
