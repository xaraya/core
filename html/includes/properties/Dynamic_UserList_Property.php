<?php
/**
 * Dynamic User List Property
 *
 * @package dynamicdata
 * @subpackage properties
 */

/**
 * Include the base class
 *
 */
include_once "includes/properties/Dynamic_Select_Property.php";

/**
 * handle the userlist property
 *
 * @package dynamicdata
 *
 */
class Dynamic_UserList_Property extends Dynamic_Select_Property
{

    function Dynamic_UserList_Property($args)
    {
        $this->Dynamic_Select_Property($args);
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            // check if this is a valid user id
            $uname = xarUserGetVar('uname', $value);
            if (isset($uname)) {
                $this->value = $value;
                return true;
            } else {
                xarExceptionHandled();
            }
        } elseif (empty($value)) {
            $this->value = $value;
            return true;
        }
        $this->invalid = xarML('selection');
        $this->value = null;
        return false;
    }

//    function showInput($name = '', $value = null, $options = array(), $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($options) || count($options) == 0) {
            $options = $this->options;
        }
        if (count($options) == 0) {

// TODO: handle large # of users too

            $users = xarModAPIFunc('roles', 'user', 'getall');
            foreach ($users as $user) {
                $options[] = array('id' => $user['uid'], 'name' => $user['name']);
            }
        }
        $out = '<select' .
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '"' .
               (!empty($id) ? ' id="'.$id.'"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '') .
               '>';
        foreach ($options as $option) {
            $out .= '<option';
            if (empty($option['id']) || $option['id'] != $option['name']) {
                $out .= ' value="'.$option['id'].'"';
            }
            if ($option['id'] == $value) {
                $out .= ' selected>'.$option['name'].'</option>';
            } else {
                $out .= '>'.$option['name'].'</option>';
            }
        }
        $out .= '</select>' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
        return $out;
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
                if (!isset($user)) xarExceptionHandled();
                $user = xarUserGetVar('uname', $value);
                if (!isset($user)) xarExceptionHandled();
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

}

?>
