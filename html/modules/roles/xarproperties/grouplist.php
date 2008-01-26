<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */
/* Include the base class */
sys::import('modules.base.xarproperties.dropdown');
/**
 * Handle Group list property
 * @author mikespub <mikespub@xaraya.com>
 */
class GroupListProperty extends SelectProperty
{
    public $id         = 45;
    public $name       = 'grouplist';
    public $desc       = 'Group List';
    public $reqmodules = array('roles');

    public $initialization_ancestorgroup_list  = null;
    public $initialization_parentgroup_list    = null;
    public $initialization_group_list          = null;
    /*
    * Options available to group selection
    * ===================================
    * Options take the form:
    *   option-type:option-value;
    * option-types:
    *   ancestor:name[,name] - select only groups who are descendants of the given group(s)
    *   parent:name[,name] - select only groups who are members of the given group(s)
    *   group:name[,name] - select only the given group(s)
    */

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/roles/xarproperties';
    }

    public function getOptions()
    {
        //allow overriding
        $options = parent::getOptions();
        if (!empty($options)) return $options;

        $select_options = array();
        if (!empty($this->initialization_ancestorgroup_list)) {
            $select_options['ancestor'] = $this->initialization_ancestorgroup_list;
        }
        if (!empty($this->initialization_parentgroup_list)) {
            $select_options['parent'] = $this->initialization_parentgroup_list;
        }
        if (!empty($this->initialization_group_list)) {
            $select_options['group'] = $this->initialization_group_list;
        }
        // TODO: handle large # of groups too (optional - less urgent than for users)
        $groups = xarModAPIFunc('roles', 'user', 'getallgroups', $select_options);
        $options = array();
        foreach ($groups as $group) {
            $options[] = array('id' => $group['id'], 'name' => $group['name']);
        }
        return $options;
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if (!empty($value)) {
            // check if this is a valid group id
            $group = xarModAPIFunc('roles','user','get',
                                   array('id' => $value,
                                         'type' => 1)); // we're looking for a group here
            if (!empty($group)) {
                $this->value = $value;
                return true;
            }
        } elseif (empty($value)) {
            $this->value = $value;
            return true;
        }
        $this->invalid = xarML('selection: #(1)', $this->name);
        $this->value = null;
        return false;
    }

    public function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) $value = $this->value;

        if (empty($value)) {
            $group = array();
            $groupname = '';
        } else {
            $group = xarModAPIFunc('roles','user','get',
                                   array('id' => $value,
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
