<?php
/**
 * File: $Id$
 *
 * Dynamic Data Group List Property
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
 * Include the base class
 *
 */
include_once "includes/properties/Dynamic_Select_Property.php";

/**
 * handle the grouplist property
 *
 * @package dynamicdata
 *
 */
class Dynamic_GroupList_Property extends Dynamic_Select_Property
{

    function Dynamic_GroupList_Property($args)
    {
        $this->Dynamic_Select_Property($args);
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            // check if this is a valid group id
            $group = xarModAPIFunc('roles','user','get',
                                   array('uid' => $value,
                                         'type' => 1)); // we're looking for a group here
            if (!empty($group)) {
                $this->value = $value;
                return true;
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
        $data = array();

        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($options) || count($options) == 0) {
            $options = $this->options;
        }
        if (count($options) == 0) {

// TODO: handle large # of groups too (optional - less urgent than for users)

            $groups = xarModAPIFunc('roles', 'user', 'getallgroups');
            foreach ($groups as $group) {
                $options[] = array('id' => $group['uid'], 'name' => $group['name']);
            }
        }
        if (empty($name)) {
            $data['name'] = 'dd_' . $this->id;
        } else {
            $data['name']= $name;
        }
        if (empty($id)) {
            $data['id'] = $data['name'];
        } else {
            $data['id'] = $id;
        }
        /* $out = '<select' .
               ' name="' . $name . '"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '') .
               '>';
        foreach ($options as $option) {
            $out .= '<option';
            if (empty($option['id']) || $option['id'] != $option['name']) {
                $out .= ' value="'.$option['id'].'"';
            }
            if ($option['id'] == $value) {
                $out .= ' selected="selected">'.$option['name'].'</option>';
            } else {
                $out .= '>'.$option['name'].'</option>';
            }
        }
        */
        /*$out .= '</select>' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
        return $out;
        */
        $data['groups']  = $groups;
        $data['value']   = $value;
        $data['options'] = $options;
        $data['tabindex']= !empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '';
        $data['invalid'] = !empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '';

        $template="grouplist";
        return xarTplModule('dynamicdata', 'admin', 'showinput', $data ,$template);

    }

    function showOutput($args = array())
    {
        extract($args);
        $data = array();

        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $group = array();
            $groupname = '';
        } else {
            $group = xarModAPIFunc('roles','user','get',
                                   array('uid' => $value,
                                         'type' => 1)); // we're looking for a group here
            if (empty($group) || empty($group['name'])) {
                $groupname = '';
            } else {
                $groupname = $group['name'];
            }
        }
        $data['value']=$value;
        $data['group']=$group;
        $data['groupname']=xarVarPrepForDisplay($groupname);
        /*if ($value > 1) {

// TODO: have some meaningful user GUI in roles to show group info ?
//       + adapt the URL below to point there :-)

            return '<a href="'.xarModURL('roles','user','display',
                                         array('uid' => $value))
                    . '">'.xarVarPrepForDisplay($groupname).'</a>';
        } else {
            return xarVarPrepForDisplay($groupname);
        }
        */
    $template="grouplist";
    return xarTplModule('dynamicdata', 'user', 'showoutput', $data ,$template);

    }

}

?>
