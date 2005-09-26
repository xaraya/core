<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */

/* Handle Group list property
 * @author mikespub <mikespub@xaraya.com>
 */

/* Include the base class */
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

class Dynamic_GroupList_Property extends Dynamic_Select_Property
{

    var $ancestorlist = array();
    var $parentlist = array();
    var $grouplist = array();

    /*
    * Options available to user selection
    * ===================================
    * Options take the form:
    *   option-type:option-value;
    * option-types:
    *   ancestor:name[,name] - select only groups who are descendants of the given group(s)
    *   parent:name[,name] - select only groups who are members of the given group(s)
    *   group:name[,name] - select only the given group(s)
    */

    function Dynamic_GroupList_Property($args)
    {
        // Don't initialise the parent class as it handles the
        // validation in an inappropriate way for user lists.
        // $this->Dynamic_Select_Property($args);
        $this->Dynamic_Property($args);

        // Handle user options if supplied.
        if (!isset($this->options)) {
            $this->options = array();
        }

        if (!empty($this->validation)) {
            foreach(preg_split('/(?<!\\\);/', $this->validation) as $option) {
                // Semi-colons can be escaped with a '\' prefix.
                $option = str_replace('\;', ';', $option);
                // An option comes in two parts: option-type:option-value
                if (strchr($option, ':')) {
                    list($option_type, $option_value) = explode(':', $option, 2);
                    if ($option_type == 'ancestor') {
                        $this->ancestorlist = array_merge($this->ancestorlist, explode(',', $option_value));
                    }
                    if ($option_type == 'parent') {
                        $this->parentlist = array_merge($this->parentlist, explode(',', $option_value));
                    }
                    if ($option_type == 'group') {
                        $this->grouplist = array_merge($this->grouplist, explode(',', $option_value));
                    }
                }
            }
        }
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
        $select_options = array();

        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($options) || count($options) == 0) {
            $options = $this->options;
        }
        if (count($options) == 0) {

            if (!empty($this->ancestorlist)) {
                $select_options['ancestor'] = implode(',', $this->ancestorlist);
            }
            if (!empty($this->parentlist)) {
                $select_options['parent'] = implode(',', $this->parentlist);
            }
            if (!empty($this->grouplist)) {
                $select_options['group'] = implode(',', $this->grouplist);
            }
// TODO: handle large # of groups too (optional - less urgent than for users)
            $groups = xarModAPIFunc('roles', 'user', 'getallgroups', $select_options);
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
        $data['tabindex']= !empty($tabindex) ? $tabindex : 0;
        $data['invalid'] = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';

        return xarTplProperty('roles', 'grouplist', 'showinput', $data);
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
        
        return xarTplProperty('roles', 'grouplist', 'showoutput', $data);
    }


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
                              'id'         =>45,
                              'name'       => 'grouplist',
                              'label'      => 'Group List',
                              'format'     => '45',
                              'validation' => '',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => 'roles',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }

}

?>
