<?php
/* Include the base class */
sys::import('modules.base.xarproperties.dropdown');

/**
 * The Grouplist property displays a dropdown of Xaraya groups
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
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
    public $show_top                        = false;

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

	/**
	 * Get the value of a dropdown
	 * 
	 * @param  string name The name of the dropdown
	 * @param  string value The value of the dropdown
	 */
    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? $this->propertyprefix . $this->id : $name;
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        
        // Get the previous group from the form
        if (!xarVar::fetch('previous_value_' . $name, 'int', $previous_value, 0, xarVar::NOT_REQUIRED)) return;
        $this->previous_groupid = $previous_value;

        return parent::checkInput();
    }

	/**
	 * Validate the value of a selected dropdown option
	 *
	 * @return bool Returns true if the value passes all validation checks; otherwise returns false.
	 */
    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;
        if (!empty($value)) {
            // check if this is a valid group id
            $group = xarMod::apiFunc('roles','user','get',
                                   array('id' => $value,
                                         'itemtype' => 2)); // we're looking for a group here
            if (!empty($group)) {
                $this->current_groupid = $value;
                return true;
            }
        } elseif (empty($value)) {
            return true;
        }
        $this->invalid = xarML('Bad selection: #(1)', $this->name);
        xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
        $this->value = null;
        return false;
    }

	/**
     * Create Value
     * 
     * @param int $itemid
     * @return boolean Returns true
     */
    public function createValue($itemid=0)
    {
        $xartable =& xarDB::getTables();
        $rolemembers = $xartable['rolemembers'];
        
        if ($this->initialization_update_behavior == 'replace' && $this->previous_groupid) {
            if (!$itemid) {
                $bindvars = array();
                $query = "DELETE FROM $rolemembers WHERE parent_id = ?";
                $bindvars[] = $this->previous_groupid;
                $dbconn = xarDB::getConn();
                $stmt = $dbconn->prepareStatement($query);
                $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
                if(!$result) return;
            } else {
                $bindvars = array();
                $query = "UPDATE FROM $rolemembers SET parent_id = ? WHERE role_id = ? AND parent_id = ?";
                $bindvars[] = $this->current_groupid;
                $bindvars[] = $itemid;
                $bindvars[] = $this->previous_groupid;
                $dbconn = xarDB::getConn();
                $stmt = $dbconn->prepareStatement($query);
                $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
                if(!$result) return;
            }
        } else {
            if (!$itemid) return true;
            $bindvars = array();
            $query = "INSERT INTO $rolemembers (role_id, parent_id) VALUES (?, ?)";
            $bindvars[] = $itemid;
            $bindvars[] = $this->current_groupid;
            $dbconn = xarDB::getConn();
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
            if(!$result) return;
        }        
        return true;
    }

	/**
     * Updates value for the given item id.
	 *
     * @param int $itemid ID of the item to be updated
     * @return boolean Returns true on success, false on failure
     */
    public function updateValue($itemid=0)
    {
        return $this->createValue($itemid);
    }

	/**
     * Deletes a value by item ID. Not implemented
     * 
     * @param int $itemid Item ID to be deleted
     * @return int Returns Item ID
     */
    public function deleteValue($itemid=0)
    {
        return $itemid;
    }

	/**
	 * Get the value by item ID
	 * @param int $itemid the item id of value
	 */
    public function retrieveValue($itemid)
    {
        $this->value = $itemid;
        $value = 0;
        $basegroup = xarRoles::get($this->initialization_basegroup);
        if (!empty($basegroup)) {
            xarMod::load('roles');
            $xartables =& xarDB::getTables();
            $rolemembers = $xartables['rolemembers'];
            $bindvars = array();
            $query = "SELECT parent_id FROM $rolemembers WHERE role_id = ?";
            $bindvars[] = $itemid;
            $dbconn = xarDB::getConn();
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
            if(!$result) return;echo $query;
            foreach ($result->next() as $row) {var_dump($row);echo "X";
                $candidate = xarRoles::get($row['parent_id']);
                if ($candidate->isAncestor($basegroup) || ($candidate->getId() == $basegroup->getId())) {
                    $value = $row['parent_id'];
                    break;
                }
            }
        }
        return $value;
    }

	/**
	 * Display a dropdown for input
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for input on a web page
	 */
    public function showInput(Array $data = array())
    {
        if (isset($data['behavior'])) $this->initialization_update_behavior = $data['behavior'];
        // CHECKME: is this needed?
        if (isset($data['basegroup'])) $this->validation_group_list = $data['basegroup'];
        if (isset($data['parent'])) $this->validation_parentgroup_list = $data['parent'];
        if (isset($data['ancestor'])) $this->validation_ancestorgroup_list = $data['ancestor'];
        if (isset($data['show_top'])) $this->show_top = $data['show_top'];

        // If we are not standalone get the group value first 
        if ($this->_itemid) {
            $data['value'] = $this->value;
//            $data['value'] = $this->retrieveValue($this->_itemid);
        }
        return parent::showInput($data);
    }

	/**
	 * Display a dropdown for output
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for output on a web page
	 */	
    public function showOutput(Array $data = array())
    {
        if (isset($data['behavior'])) $this->initialization_update_behavior = $data['behavior'];
        if (isset($data['basegroup'])) $this->validation_group_list = $data['basegroup'];

        // If we are not standalone get the group value first 
        if ($this->_itemid) {
            // It's a standalone property
            $data['value'] = $this->retrieveValue($this->_itemid);
        } elseif (isset($data['_itemid'])) {
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

	/**
     * Retrieve the list of options
     * 
     * @param void N/A
     */
    public function getOptions()
    {
        $select_options = array();
        $select_options['show_top'] = $this->show_top;
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
        $options = $this->getFirstline();
        $options = array_merge($options,xarMod::apiFunc('roles', 'user', 'getallgroups', $select_options));
        return $options;
    }

}

?>