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
    var $grouplist = array();
    var $userstate = -1;
    var $showlist = array();
    var $orderlist = array();
    var $showglue = '; ';

    /*
    * Options available to user selection
    * ===================================
    * Options take the form:
    *   option-type:option-value;
    * option-types:
    *   group:name[,name] - select only users who are members of the given group(s)
    *   state:value - select only users of the given state
    *   show:field[,field] - show the specified field(s) in the select item
    *   showglue:string - string to join multiple fields together
    *   order:field[,field] - order the selection by the specified field
    * where
    *   field - name|uname|email|uid
    */

    function Dynamic_UserList_Property($args)
    {
        // Don't initialise the parent class as it handles the
        // validation in an inappropriate way for user lists.
        // $this->Dynamic_Select_Property($args);
        $this->Dynamic_Property($args);

        // Initialise the select option list.
        $this->options = array();

        // Handle user options if supplied.
        if (!empty($this->validation)) {
            foreach(preg_split('/(?<!\\\);/', $this->validation) as $option) {
                // Semi-colons can be escaped with a '\' prefix.
                $option = str_replace('\;', ';', $option);
                // An option comes in two parts: option-type:option-value
                if (strchr($option, ':')) {
                    list($option_type, $option_value) = explode(':', $option, 2);
                    if ($option_type == 'state' && is_numeric($option_value)) {
                        $this->userstate = $option_value;
                    }
                    if ($option_type == 'showglue') {
                        $this->showglue = $option_value;
                    }
                    if ($option_type == 'group') {
                        $this->grouplist = array_merge($this->grouplist, explode(',', $option_value));
                    }
                    if ($option_type == 'show') {
                        $this->showlist = array_merge($this->showlist, explode(',', $option_value));
                        // Remove invalid elements (fields that are not valid).
                        $showfilter = create_function(
                            '$a', 'return preg_match(\'/^[-]?(name|uname|email|uid|state|date_reg)$/\', $a);'
                        );
                        $this->showlist = array_filter($this->showlist, $showfilter);
                    }
                    if ($option_type == 'order') {
                        $this->orderlist = array_merge($this->orderlist, explode(',', $option_value));
                    }
                }
            }
        }
    }

    // TODO: validate the selected user against the specified group(s).
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

    function showInput($args = array())
    {
        $select_options = array();

        extract($args);

        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($options) || count($options) == 0) {
            $options = $this->options;
        }
        if (count($options) == 0) {
            if ($this->userstate <> -1) {
                $select_options['state'] = $this->userstate;
            }
            if (!empty($this->orderlist)) {
                $select_options['order'] = implode(',', $this->orderlist);
            }
            if (!empty($this->grouplist)) {
                $select_options['group'] = implode(',', $this->grouplist);
            }

            $users = xarModAPIFunc('roles', 'user', 'getall', $select_options);

            // Loop for each user retrived and populate the options array.
            if (empty($this->showlist)) {
                // Simple case (default) - 
                foreach ($users as $user) {
                    $options[] = array('id' => $user['uid'], 'name' => $user['name']);
                }
            } else {
                // Complex case: allow specific fields to be selected.
                foreach ($users as $user) {
                    $namevalue = array();
                    foreach ($this->showlist as $showfield) {
                        $namevalue[] = $user[$showfield];
                    }
                    $options[] = array('id' => $user['uid'], 'name' => implode($this->showglue, $namevalue));
                }
            }
        }

        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }

        if (empty($id)) {
            // TODO: strip out characters that are not allowed in a name.
            $id = $name;
        }

        $out = '<select' .
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

        $out .= '</select>' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');

        return $out;
    }

    // TODO: format the output according to the 'showlist'.
    // TODO: provide an option to allow admin to decide whether to wrap the user
    // in a link or not.
    function showOutput($args = array())
    {
        extract($args);

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
            return '<a href="'.xarModURL('roles', 'user', 'display',
                                         array('uid' => $value))
                    . '">'.xarVarPrepForDisplay($user).'</a>';
        } else {
            return xarVarPrepForDisplay($user);
        }
    }
}

?>
