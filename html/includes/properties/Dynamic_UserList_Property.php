<?php
/**
 * Dynamic User List Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */

include_once "includes/properties/Dynamic_Select_Property.php";

class Dynamic_UserList_Property extends Dynamic_Select_Property
{

    function Dynamic_UserList_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {

// TODO: handle large # of users too

            $users = xarModAPIFunc('roles', 'user', 'getall');
            foreach ($users as $user) {
                $this->options[] = array('id' => $user['uid'], 'name' => $user['name']);
            }
        }
    }

    function showOutput($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $user = '';
        } else {
            $user = xarUserGetVar('name', $value);
            if (empty($user)) {
                $user = xarUserGetVar('uname', $value);
            }
        }
        if ($value > 1) {
            return '<a href="'.xarModURL('roles','user','display',
                                         array('uid' => $value))
                    . '">'.xarVarPrepForDisplay($user).'</a>';
        } else {
            return xarVarPrepForDisplay($user);
        }
    }

    // default showInput() from Dynamic_Select_Property

}

?>
