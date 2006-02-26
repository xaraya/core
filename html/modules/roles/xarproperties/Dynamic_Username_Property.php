<?php
/**
 * Handle Username Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */

/**
 * Handle Username Property
 * @author mikespub <mikespub@xaraya.com>
 */

class Dynamic_Username_Property extends Dynamic_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->requiresmodule = 'roles';

        $this->id     = 7;
        $this->name   = 'username';
        $this->label  = 'Username';
        $this->format = '7';
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = xarUserGetVar('uid');
        }
        // check that the user exists, but dont except
        if (is_numeric($value)) {
            try {
                $user = xarUserGetVar('uname', $value);
            } catch (NotFoundExceptions $e) {
                // Nothing to do?
            }
        }
        if (!is_numeric($value) || empty($user)) {
            $this->invalid = xarML('user');
            $this->value = null;
            return false;
        } else {
            $this->value = $value;
            return true;
        }
    }

//    function showInput($name = '', $value = null, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = xarUserGetVar('uid');
        }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        $data = array();

        try {
            $user = xarUserGetVar('name', $value);

            if (empty($user)) 
                $user = xarUserGetVar('uname', $value);
        } catch (NotFoundExceptions $e) {
            // Nothing to do?
        }

        if ($value > 1) {
            $data['linkurl'] = xarModURL('roles','user','display', array('uid' => $value));
        }
        $data['user'] = xarVarprepForDisplay($user);
        $data['value']= $value;
        $data['name'] = $name;
        $data['id']   = $id;
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
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = xarUserGetVar('uid');
        }
        $data=array();
        try {
            $user = xarUserGetVar('name', $value);
            if (empty($user)) 
                $user = xarUserGetVar('uname', $value);
        } catch(NotFoundExceptions $e) {
            // Nothing to do?
        }

        $data['value'] = $value;
        $data['user']  = xarVarPrepForDisplay($user);
        $data['name']  = $this->name;
        $data['id']    = $this->id;

        if ($value > 1) {
            $data['linkurl'] = xarModURL('roles','user','display',array('uid' => $value));
        }

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
