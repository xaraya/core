<?php
/**
 * Dynamic Username Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Username_Property extends Dynamic_Property
{
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = xarUserGetVar('uid');
        }
        // check that the user exists
        if (is_numeric($value)) {
            $user = xarUserGetVar('uname', $value);
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
        $user = xarUserGetVar('name', $value);
        if (empty($user)) {
            $user = xarUserGetVar('uname', $value);
        }
        $output = xarVarPrepForDisplay($user);
        if ($value > 1) {
            $output .= ' [ <a href="'.xarModURL('roles','user','display',
                                         array('uid' => $value))
                    . '" target="preview">'.xarML('profile').'</a> ]';
        }
        if (!empty($this->invalid)) {
            $output .= ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>';
        }
        return $output;
    }

    function showOutput($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = xarUserGetVar('uid');
        }
        $user = xarUserGetVar('name', $value);
        if (empty($user)) {
            $user = xarUserGetVar('uname', $value);
        }
        if ($value > 1) {
            return '<a href="'.xarModURL('roles','user','display',
                                         array('uid' => $value))
                    . '">'.xarVarPrepForDisplay($user).'</a>';
        } else {
            return xarVarPrepForDisplay($user);
        }
    }

}

?>