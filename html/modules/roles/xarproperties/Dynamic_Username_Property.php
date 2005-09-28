<?php
/**
 * File: $Id$
 *
 * Dynamic Data Username Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata properties
 * @author mikespub <mikespub@xaraya.com>
*/

/**
 * handle username property
 *
 * @package dynamicdata
 *
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
            if (!isset($user)) xarErrorHandled();
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
        $data=array();

        $user = xarUserGetVar('name', $value);

        if (empty($user)) {
            if (!isset($user)) xarErrorHandled();
            $user = xarUserGetVar('uname', $value);
            if (!isset($user)) xarErrorHandled();
        }

        if ($value > 1) {
/*            $output .= ' [ <a href="'.xarModURL('roles','user','display',
                                         array('uid' => $value))
                    . '" target="preview">'.xarML('profile').'</a> ]';
*/
            $data['linkurl'] = xarModURL('roles','user','display', array('uid' => $value));
        }
        $data['user'] = xarVarprepForDisplay($user);
        $data['value']= $value;
        $data['name'] = $name;
        $data['id']   = $id;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        return xarTplProperty('roles', 'username', 'showinput', $data);
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
        $user = xarUserGetVar('name', $value);
        if (empty($user)) {
            if (!isset($user)) xarErrorHandled();
            $user = xarUserGetVar('uname', $value);
            if (!isset($user)) xarErrorHandled();
        }

        $data['value'] = $value;
        $data['user']  = xarVarPrepForDisplay($user);
        $data['name']  = $this->name;
        $data['id']    = $this->id;

        if ($value > 1) {
            $data['linkurl']=xarModURL('roles','user','display',array('uid' => $value));
/*          return '<a href="'.xarModURL('roles','user','display',
                                         array('uid' => $value))
                    . '">'.xarVarPrepForDisplay($user).'</a>';
*/
        }

        return xarTplProperty('roles', 'username', 'showoutput', $data);
    }


    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $baseInfo = array(
                              'id'         => 7,
                              'name'       => 'username',
                              'label'      => 'Username',
                              'format'     => '7',
                              'validation' => '',
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => 'roles',
                            'aliases' => '',
                            'args'         => '',
                            // ...
                           );
        return $baseInfo;
     }

}

?>
