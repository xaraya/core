<?php
/**
 * Hidden property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */

/**
 * Class to handle hidden properties
 * @author mikespub <mikespub@xaraya.com>
 * @package dynamicdata
 */
class Dynamic_Hidden_Property extends Dynamic_Property
{
    function __construct($args) {
        parent::__construct($args);
        $this->id = 18;
        $this->name = 'hidden';
        $this->label = 'Hidden'; // ML this?
        $this->format = '18';
        $this->requiresmodule = 'base'; // This needed here?
        $this->tplmodule = 'base';
    }

    function validateValue($value = null)
    {
        if (isset($value) && $value != $this->value) {
            $this->invalid = xarML('hidden field');
            $this->value = null;
            return false;
        } else {
            return true;
        }
    }

//    function showInput($name = '', $value = null)
    function showInput($args = array())
    {
        extract($args);
        $data = array();

        $data['name']     = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']       = !empty($id)   ? $id   : 'dd_'.$this->id;
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

    function showOutput($args = array())
    {
        extract($args);

        $data=array();
        $data['hiddenvalue']='';

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
