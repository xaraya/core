<?php
/**
 * @package modules
 * @copyright (C) copyright-placeholder
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

    public $previous_groupid = 0;
    public $current_groupid  = 0;

    public $initialization_update_behavior  = 'replace';
    public $initialization_basegroup        = 1;
    public $validation_ancestorgroup_list   = null;
    public $validation_parentgroup_list     = null;
    public $validation_group_list           = null;
    public $validation_override             = true;

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

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        
        // Get the previous group from the form
        if (!xarVarFetch($name . '_previous_value', 'int', $previous_value, 0, XARVAR_NOT_REQUIRED)) return;
        $this->previous_groupid = $previous_value;

        return parent::checkInput();
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;
        if (!empty($value)) {
            // check if this is a valid group id
            $group = xarModAPIFunc('roles','user','get',
                                   array('id' => $value,
                                         'itemtype' => 3)); // we're looking for a group here
            if (!empty($group)) {
                $this->current_groupid = $value;
                return true;
            }
        } elseif (empty($value)) {
            return true;
        }
        $this->invalid = xarML('selection: #(1)', $this->name);
        $this->value = null;
        return false;
    }

    public function createValue($itemid=0)
    {
        $xartable = xarDB::getTables();
        
        if ($this->initialization_update_behavior == 'replace' && $this->previous_groupid) {
            if (!$itemid) {
                $q = new xarQuery('DELETE',$xartable['rolemembers']);
                $q->eq('parent_id',$this->previous_groupid);
                if (!$q->run()) return;
            } else {
                $q = new xarQuery('UPDATE',$xartable['rolemembers']);
                $q->addfield('parent_id',$this->current_groupid);
                $q->eq('role_id',$itemid);
                $q->eq('parent_id',$this->previous_groupid);
                if (!$q->run()) return;
            }
        } else {
            if (!$itemid) return true;
            $q = new xarQuery('INSERT',$xartable['rolemembers']);
            $q->addfield('role_id',$itemid);
            $q->addfield('parent_id',$this->current_groupid);
            if (!$q->run()) return;
        }        
        return true;
    }

    public function updateValue($itemid=0)
    {
        return $this->createValue($itemid);
    }

    public function deleteValue($itemid=0)
    {
        return $itemid;
    }

    public function retrieveValue($itemid)
    {
        $this->value = $itemid;
        $value = 0;
        $basegroup = xarRoles::get($this->initialization_basegroup);
        if (!empty($basegroup)) {
            $xartable = xarDB::getTables();
            $q = new xarQuery('SELECT',$xartable['rolemembers']);
            $q->addfield('parent_id');
            $q->eq('role_id',$itemid);
            if (!$q->run()) return;
            foreach ($q->output() as $row) {
                $candidate = xarRoles::get($row['parent_id']);
                if ($candidate->isAncestor($basegroup) || ($candidate->getId() == $basegroup->getId())) {
                    $value = $row['parent_id'];
                    break;
                }
            }
        }
        return $value;
    }

    public function showInput(Array $data = array())
    {
        if (isset($data['behavior'])) $this->initialization_update_behavior = $data['behavior'];
        if (isset($data['basegroup'])) $this->initialization_basegroup = $data['basegroup'];

        // If we are not standalone get the group value first 
        if ($this->_itemid) {
            $data['value'] = $this->retrieveValue($this->_itemid);
        }
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (isset($data['behavior'])) $this->initialization_update_behavior = $data['behavior'];
        if (isset($data['basegroup'])) $this->initialization_basegroup = $data['basegroup'];

        // If we are not standalone get the group value first 
        if ($this->_itemid) {
            // It's a standalone property
            $data['value'] = $this->retrieveValue($this->_itemid);
        } elseif ($data['_itemid']) {
            // It's a row in an objectlist
            $data['value'] = $this->retrieveValue($data['_itemid']);
        }
        $group = xarRoles::get($data['value']);
        if (!empty($group)) {
            $data['value'] = $group->getName();
        } else {
            $data['value'] = '';
        }
        return parent::showOutput($data);
    }

    public function getOptions()
    {
        $select_options = array();
        if (!empty($this->validation_ancestorgroup_list)) {
            $select_options['ancestor'] = $this->validation_ancestorgroup_list;
        }
        if (!empty($this->validation_parentgroup_list)) {
            $select_options['parent'] = $this->validation_parentgroup_list;
        }
        if (!empty($this->validation_group_list)) {
            $select_options['group'] = $this->validation_group_list;
        }
        // TODO: handle large # of groups too (optional - less urgent than for users)
        $groups = xarModAPIFunc('roles', 'user', 'getallgroups', $select_options);
        return $groups;
    }

}

?>