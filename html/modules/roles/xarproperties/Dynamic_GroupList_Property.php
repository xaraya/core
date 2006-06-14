<?php
/**
 * Handle Group list property
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */

/*
 * Handle Group list property
 * @author mikespub <mikespub@xaraya.com>
 */

/* Include the base class */
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

class Dynamic_GroupList_Property extends Dynamic_Select_Property
{
    public $ancestorlist = array();
    public $parentlist   = array();
    public $grouplist    = array();

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

    function __construct($args)
    {
        parent::__construct($args);

        if (count($this->options) == 0) {
	        $select_options = array();
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
            $this->options = $options;
        }

    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('roles');
        $info->id = 45;
        $info->name = 'grouplist';
        $info->desc = 'Group List';
        $info->reqmodules = array('roles');
        return $info;
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

    function parseValidation($validation = '')
    {
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

    function showOutput($data = array())
    {
        extract($data);

        if (!isset($value)) $value = $this->value;
        
        if (empty($value)) {
            $group = array();
            $groupname = '';
        } else {
            $group = xarModAPIFunc('roles','user','get',
                                   array('uid' => $value,
                                         'type' => ROLES_GROUPTYPE)); // we're looking for a group here
            if (empty($group) || empty($group['name'])) {
                $groupname = '';
            } else {
                $groupname = $group['name'];
            }
        }
        $data['value']=$value;
        $data['group']=$group;
        $data['groupname']=xarVarPrepForDisplay($groupname);

        return parent::showOutput($data);
    }
}

?>
