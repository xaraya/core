<?php
/**
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */

sys::import('modules.dynamicdata.class.objects.base');

/**
 * Role: class for the role object
 *
 * Represents a single role (user or group)
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 */
class Role extends DataObject
{
    public $parentlevel;  //we use this just to store transient information

    public $rolestable;
    public $rolememberstable;
    public $privilegestable;
    public $acltable;
    public $realmstable;
    public $modulestable;

    public $allprivileges;

    public $visibility = 'private';

    /**
     * Role: constructor for the role object
     *
     * Retrieves a single role (user or group) from the roles repository
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array $pargs
     * @return object role
     */
    public function __construct(DataObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        // dodgy. remove later on
        sys::import('modules.privileges.xartables');
        xarDB::importTables(privileges_xartables());

        $xartable =& xarDB::getTables();
        $this->rolestable = $xartable['roles'];
        $this->rolememberstable = $xartable['rolemembers'];
        $this->privilegestable = $xartable['privileges'];
        $this->acltable = $xartable['security_acl'];
        $this->realmstable = $xartable['security_realms'];
        $this->modulestable = $xartable['modules'];

        $this->parentlevel = 0;
    }

    /**
     * createItem: add a new role item to the repository
     *
     * Creates an entry in the repository for a role object that has been created
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return boolean
     */
    public function createItem(Array $data = array())
    {
        // Confirm that this group or user does not already exist
        xarMod::loadDbInfo('roles','roles');
        $xartable =& xarDB::getTables();
        $dynamicobjects = $this->rolestable;
        $bindvars = array();
        $query = "SELECT name, uname
                  FROM $dynamicobjects ";
        if ($this->itemtype == xarRoles::ROLES_GROUPTYPE) {
            if (empty($data['name'])) $data['name'] = $this->getName();
            $query .= " WHERE name = ? ";
            $bindvars[] = $data['name'];
        } else {
            if (empty($data['uname'])) $data['uname'] = $this->getUser();
            $query .= " WHERE uname = ? ";
            $bindvars[] = $data['uname'];
        }
        $dbconn = xarDB::getConn();
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
        if ($result->getRow() > 0) {
            $result = $query->row();
            throw new DuplicateException(array('role',($this->itemtype == xarRoles::ROLES_GROUPTYPE) ? $result['name'] :$result['uname'] ));
        }

        $result->close();

        $id = parent::createItem($data);

        // Set the email useage for this user to false
        xarModUserVars::set('roles','allowemail', false, $id);

        // Get a value for the parent id
        if (empty($data['parentid'])) xarVarFetch('parentid',  'int', $data['parentid'],  NULL, XARVAR_DONT_SET);
        if (empty($data['parentid'])) $data['parentid'] = (int)xarModVars::get('roles', 'defaultgroup');
        if (!empty($data['parentid'])) {
            sys::import('modules.roles.class.roles');
            $parent = xarRoles::get($data['parentid']);
            if (!$parent->addMember($this))
                throw new Exception('Unable to create a roles relation');
        }

        // add the duvs
        if (!xarVarFetch('duvs','array',$duvs,array(),XARVAR_NOT_REQUIRED)) return;
        foreach($duvs as $key => $value) {
            xarModUserVars::set('roles',$key, $value, $id);
        }

        // Let any hooks know that we have created a new user.
        $item['module'] = 'roles';
        $item['itemtype'] = $this->getType();
        $item['itemid'] = $id;
        $item['exclude_module'] = array('dynamicdata');
        xarModCallHooks('item', 'create', $id, $item);
        return $id;
    }

    public function updateItem(Array $data = array())
    {
        $id = parent::updateItem($data);
        if (!xarVarFetch('duvs','array',$duvs,array(),XARVAR_NOT_REQUIRED)) return;
        foreach($duvs as $key => $value) {
            xarModUserVars::set('roles',$key, $value, $id);
        }
        $item['module'] = 'roles';
        $item['itemtype'] = $this->getType();
        $item['itemid'] = $id;
        $item['exclude_module'] = array('dynamicdata');
        xarModCallHooks('item', 'update', $id, $item);
        return $id;
    }

    /**
     * addMember: adds a role to a group
     *
     * Make a user or group a member of another group.
     * A user of group can have any number of parents or children..
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param object $member
     * @return boolean
     */
    public function addMember($member)
    {
        // bail if the purported parent is not a group
        if ($this->isUser()) return false;

        $query = "SELECT * FROM $this->rolememberstable
                 WHERE role_id = ? AND parent_id = ?";

        $bindvars[] = $member->getID();
        $bindvars[] = $this->getID();

        $dbconn = xarDB::getConn();
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
        
        // If the relation already exists we are done
        while($result->next()) $row = $result->fields;
        if (!empty($row)) return true;

        $query = "INSERT INTO $this->rolememberstable (role_id, parent_id)
                    values (".$member->getID().", ". $this->getID().")";

        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);

        // for children that are users
        // add 1 to the users field of the parent group. This is for display purposes.
        if ($member->isUser()) {
            // get the current count
            $bindvars = array();
            $query = "SELECT  users
                        FROM $this->rolestable";
            $query .= " WHERE id = ?";
            $bindvars[] =  $this->getID();
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
            if (!$result) return;
            while($result->next()) $row = $result->fields;

            // add 1 and update.
            $bindvars = array();
            $value = $row['users']+1;
            $query = "UPDATE  " . $this->rolestable . " SET users = " . $value . " WHERE id = ?";
            $bindvars[] =  $this->getID();
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);

        }

        $item['module']   = 'roles';
        $item['itemtype'] = $this->getType();
        $item['itemid']   = $this->getID();
        xarModCallHooks('item', 'link', $this->getID(), $item);

        // Refresh the privileges cached for the current sessions
        xarMasks::clearCache();
        return true;
    }

    /**
     * removeMember: removes a role from a group
     *
     * Removes a user or group as an entry of another group.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param object $member
     * @return boolean
     * @todo add transaction around the delete and the update
     */
    public function removeMember($member)
    {
        // Delete the relevant entry from the rolemembers table
        $xartables =& xarDB::getTables();
        sys::import('xaraya.structures.query');
        $q = new Query('DELETE', $xartables['rolemembers']);
        $q->eq('role_id', $member->getID());
        $q->eq('parent_id', $this->getID());
        $q->run();

        // For children that are users subtract 1 from the users field of the parent group. 
        // This is for display purposes.
        if ($member->isUser() && ($q->affected != 0)) {
            // get the current count.
            $bindvars = array();
            $query = "SELECT  users
                        FROM $this->rolestable";
            $query .= " WHERE id = ?";
            $bindvars[] =  $this->getID();
            $dbconn = xarDB::getConn();
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
            if (!$result) return;
            while($result->next()) $row = $result->fields;

            // subtract 1 and update.
            $dynamicobjects = $this->rolestable;
            $value = $row['users']-1;
            $query = "UPDATE  " . $dynamicobjects . " SET users = " . $value . " WHERE id = ?";
            $bindvars[] =  $this->getID();

            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
        }
        $item['module']   = 'roles';
        $item['itemtype'] = $this->getType();
        $item['itemid']   = $this->getID();
        xarModCallHooks('item', 'unlink', $this->getID(), $item);

        // Refresh the privileges cached for the current sessions
        xarMasks::clearCache();
        return true;
    }

    /**
     * deleteItem: make a role deleted
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return boolean
     * @todo flag illegal deletes
     */
    public function deleteItem(Array $data = array())
    {
        if (!empty($data['itemid'])) $this->setID($data['itemid']);

        if($this->getID() == (int)xarModVars::get('roles','defaultgroup'))
            return xarTpl::module('roles','user','errors',array('layout' => 'remove_defaultusergroup', 'group' => $this->getID()));

        // get a list of all relevant entries in the rolemembers table
        // where this role is the child
        $query = "SELECT parent_id FROM $this->rolememberstable WHERE role_id= ?";
        // Execute the query, bail if an exception was thrown
        $dbconn = xarDB::getConn();
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($this->getID()));

        if(count($result->fields) == 1)
            return xarTpl::module('roles','user','errors',array('layout' => 'remove_sole_parent'));

        sys::import('modules.roles.class.roles');
        // go through the list, retrieving the roles and detaching each one
        // we need to do it this way because the method removeMember is more than just
        // a simple SQL DELETE
        while ($result->next()) {
            list($parentid) = $result->fields;
            $parent = xarRoles::get($parentid);
            // Check that a parent was returned
            if ($parent) {
                $parent->removeMember($this);
            }
        }

        //Let's not remove the role yet. Instead, we want to deactivate it
        $deleted = xarML('deleted');
        $args = array(
            'itemid' => $this->getID(),
            'user' => "[" . $deleted . "]" . time(),
            'email' => "[" . $deleted . "]" . time(),
            'state' => xarRoles::ROLES_STATE_DELETED,
        );
        if (isset($data['authmodule'])) $args['authmodule'] = $data['authmodule'];
        $this->updateItem($args);

        // get all the privileges that were assigned to this role
        $privileges = $this->getAssignedPrivileges();
        // remove the privilege assignments for this role
        foreach ($privileges as $priv) {
            $this->removePrivilege($priv);
        }

        // Let any hooks know that we have deleted this user.
        $item['module'] = 'roles';
        $item['itemid'] = $this->getID();
        $item['method'] = 'delete';
        $item['exclude_module'] = array('dynamicdata');
        xarModCallHooks('item', 'delete', $this->getID(), $item);

        // CHECKME: re-assign all privileges to the child roles ? (probably not)
        return true;
    }


    /**
     * purge: make a role purged
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return boolean
     */
    public function purge()
    {
        // no checks here. just do it
        $this->deleteItem();
        $state = xarRoles::ROLES_STATE_DELETED;
        $uname = xarML('deleted') . microtime(TRUE) .'.'. $this->properties['id']->value;
        $name = '';
        $pass = '';
        $email = '';
        $date_reg = '';

        xarMod::loadDbInfo('roles','roles');
        $xartable =& xarDB::getTables();
        $bindvars = array();
        $query = "UPDATE $this->rolestable
                  SET name = $name,
                      uname = $uname,
                      pass = $pass,
                      email = $email,
                      date_reg = ".time().",
                      state = $state";

        $query .= " WHERE id = ? ";
        $bindvars[] = $this->getID();
        $dbconn = xarDB::getConn();
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
        $item['module'] = 'roles';
        $item['itemid'] = $this->getID();
        $item['itemtype'] = $this->getType();
        $item['method'] = 'purge';
        xarModCallHooks('item', 'delete', $this->getID(), $item);
        return true;
    }

    /**
     * Gets all the privileges assigned directly to this role.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return array of privilege objects
     * @todo seems to me this belongs in privileges.
     */
    public function getAssignedPrivileges()
    {
        static $stmt = null;  // For each id, the query is the same, prepare it once.

        $cacheKey = "Privileges.ById";
        if(xarVarIsCached($cacheKey,$this->properties['id']->value)) {
            return xarVarGetCached($cacheKey,$this->properties['id']->value);
        }
        // We'll have to get it.
        xarLog::message("ROLE: getting privileges for id: " . $this->properties['id']->value, xarLog::LEVEL_INFO);
        // TODO: propagate the use of 'All'=null for realms through the API instead of the flip-flopping
        $xartable =& xarDB::getTables();
        $query = "SELECT  p.id, p.name, r.name, p.module_id, m.name,
                          component, instance, level, description
                  FROM    $this->acltable acl,
                          $this->privilegestable p
                          LEFT JOIN $this->realmstable r ON p.realm_id = r.id
                          LEFT JOIN $this->modulestable m ON p.module_id = m.id
                  WHERE   p.id = acl.privilege_id AND
                          acl.role_id = ?";
//                          echo $query;exit;
        if(!isset($stmt)) {
            $dbconn = xarDB::getConn();
            $stmt = $dbconn->prepareStatement($query);
        }
        $result = $stmt->executeQuery(array($this->properties['id']->value));

        sys::import('modules.privileges.class.privilege');
        $privileges = array();
        while ($result->next()) {
            list($id, $name, $realm, $module_id, $module, $component, $instance, $level,
                $description) = $result->fields;
            $perm = new xarPrivilege(array('id' => $id,
                    'name' => $name,
                    'realm' => is_null($realm) ? 'All' : $realm,
                    'module' => $module,
                    'module_id' => $module_id,
                    'component' => $component,
                    'instance' => $instance,
                    'level' => $level,
                    'description' => $description,
                    'parentid' => 0));
            array_push($privileges, $perm);
        }
        xarVarSetCached($cacheKey,$this->properties['id']->value,$privileges);
        return $privileges;
    }


    /**
     * Gets all the privileges inherited by this role.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return array of privilege objects
     */
    public function getInheritedPrivileges()
    {
        // mrb: is this only dependent on $this->properties['id']->value? if so, we can cache it too.
        $ancestors = $this->getRoleAncestors();
        $inherited = array();
        foreach ($ancestors as $ancestor) {
            $perms = $ancestor->getAssignedPrivileges();
            while (list($key, $perm) = each($perms)) {
                array_push($inherited, $perm);
            }
        }
        return $inherited;
    }

    /**
     * Checks whether this role has a specific privilege assigned or inherited.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return boolean
     */
    public function hasPrivilege($privname)
    {
        $privs = $this->getAssignedPrivileges();
        foreach ($privs as $privilege)
            if ($privilege->getName() == $privname) return true;
        $privs = $this->getInheritedPrivileges();
        foreach ($privs as $privilege)
            if ($privilege->getName() == $privname) return true;
        return false;
    }

    /**
     * assignPrivilege: assigns a privilege to a role
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param object $privilege
     * @return boolean
     */
    public function assignPrivilege($privilege)
    {
        // create an entry in the privmembers table
        $query = "INSERT INTO $this->acltable VALUES (?,?)";
        $bindvars = array($this->getID(),$privilege->getID());
        $dbconn = xarDB::getConn();
        $dbconn->Execute($query,$bindvars);

        // Refresh the privileges cached for the current sessions
        xarMasks::clearCache();
        return true;
    }

    /**
     * removePrivilege: removes a privilege from a role
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param object $privilege
     * @return boolean
     */
    public function removePrivilege($privilege)
    {
        // remove an entry from the privmembers table
        $query = "DELETE FROM $this->acltable
                  WHERE role_id= ? AND privilege_id= ?";
        $bindvars = array($this->properties['id']->value, $privilege->getID());
        $dbconn = xarDB::getConn();
        $dbconn->Execute($query,$bindvars);

        // Refresh the privileges cached for the current sessions
        xarMasks::clearCache();
        return true;
    }

    /**
     * getUsers: get the members of a group that are users
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param integer state get users in this state
     * @param integer startnum get users beyond this number
     * @param integer numitems get a defined number of users
     * @param string order order the result (name, uname, itemtype, email, date_reg, state...)
     * @param string selection get users within this selection criteria
     * @return array
     */
    public function getUsers($state = xarRoles::ROLES_STATE_CURRENT, $startnum = 0, $numitems = 0, $order = 'name', $selection = NULL)
    {
        $query = "SELECT r.id, r.name, r.itemtype, r.uname,
                         r.email, r.pass, r.date_reg,
                         r.valcode, r.state,r.auth_module_id
                  FROM $this->rolestable r, $this->rolememberstable rm ";
        // set up the query and get the data
        if ($state == xarRoles::ROLES_STATE_CURRENT) {
            $where = "WHERE r.id = rm.role_id AND
                        r.itemtype = ? AND
                        r.state != ? AND
                        rm.parent_id = ?";
             $bindvars = array(xarRoles::ROLES_USERTYPE,xarRoles::ROLES_STATE_DELETED,$this->getID());
        } elseif ($state == xarRoles::ROLES_STATE_ALL) {
            $where = "WHERE r.id = rm.role_id AND
                        r.itemtype = ? AND
                        rm.parent_id = ?";
             $bindvars = array(xarRoles::ROLES_USERTYPE,$this->getID());
        } else {
             $bindvars = array(xarRoles::ROLES_USERTYPE, $state, $this->properties['id']->value);
            $where = "WHERE r.id = rm.role_id AND
                        r.itemtype = ? AND
                        r.state = ? AND
                        rm.parent_id = ?";
        }
        $query .= $where;
        if (isset($selection)) $query .= $selection;
        $query .= " ORDER BY " . $order;
        // Prepare the query
        $dbconn = xarDB::getConn();
        $stmt = $dbconn->prepareStatement($query);

        if ($startnum != 0) {
            $stmt->setLimit($numitems);
            $stmt->setOffset($startnum - 1);
        }
        $result = $stmt->executeQuery($bindvars);

        // CHECKME: I suppose this is what you meant here ?
        $parentid = $this->getID();
        // arrange the data in an array of role objects
        $users = array();
        while ($result->next()) {
            list($id) = $result->fields;

            $role = DataObjectMaster::getObject(array('name' => 'roles_users'));
            $role->getItem(array('itemid' => $id));
            $users[] = $role;
        }
        // done
        return $users;
    }

    /**
     * countChildren: count the members of a group
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param integer state count user in this state
     * @param string selection count user within this selection criteria
     * @param integer itemtype group or user
     * @return integer
     */
    public function countChildren($state = xarRoles::ROLES_STATE_CURRENT, $selection = NULL, $itemtype = NULL)
    {
        $xartable =& xarDB::getTables();
        $rolesmemobjects = $this->rolememberstable;
        $rolesobjects = $this->rolestable;
        $bindvars = array();
        $query = "SELECT COUNT(r.id) AS children FROM $rolesobjects AS r
                JOIN $rolesmemobjects AS rm ON(r.id = rm.role_id)";

        $query .= " WHERE rm.parent_id = ? ";
        $bindvars[] = $this->properties['id']->value;
        if ($state == xarRoles::ROLES_STATE_CURRENT) {
            $query .= " AND r.state != ? ";
            $bindvars[] = xarRoles::ROLES_STATE_DELETED;
        } else {
            $query .= " AND r.state = ? ";
            $bindvars[] = $state;
        }
        $dbconn = xarDB::getConn();
        if (isset($itemtype))
            $query .= " AND r.itemtype = ? ";
            $bindvars[] = $itemtype;
        if (isset($selection)) {
            $query = $selection;
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
        } else {
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
        }
        if($result) return;
        while ($result->next()) $row = $result->fields;

        return $row['children'];
    }

    /**
     * countUsers: count the members of a group that are users
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param int    $state count user in this state
     * @param string $selection count user within this selection criteria
     * @return integer
     */
    public function countUsers($state = xarRoles::ROLES_STATE_CURRENT, $selection = NULL)
    {
        return $this->countChildren(0, $state, $selection);
    }

    /**
     * getParents: returns the parent objects of a role
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return array of role objects
     */
    public function getParents()
    {
        static $stmt = null;  // The query below is the same for each id, prepare it once.

        $cacheKey = 'RoleParents.ById';
        // create an array to hold the objects to be returned
        $parents = array();
        // if this is the root return an empty array
        if ($this->getID() == 1) return $parents;

        // if it's cached, we can return it
        if(xarVarIsCached($cacheKey,$this->properties['id']->value)) {
            return xarVarGetCached($cacheKey,$this->properties['id']->value);
        }

        // if this is a user just perform a SELECT on the rolemembers table
        $query = "SELECT r.*
                  FROM $this->rolestable r, $this->rolememberstable rm
                  WHERE r.id = rm.parent_id AND rm.role_id = ?";
        if(!isset($stmt)) {
            $dbconn = xarDB::getConn();
            $stmt = $dbconn->prepareStatement($query);
        }
        $result = $stmt->executeQuery(array($this->properties['id']->value));

        // collect the table values and use them to create new role objects
        while ($result->next()) {
            list($id) = $result->fields;

            $role = DataObjectMaster::getObject(array('name' => 'roles_groups'));
            $role->getItem(array('itemid' => $id));
            $parents[] = $role;
        }
        // done
        xarVarSetCached($cacheKey,$this->properties['id']->value,$parents);
        return $parents;
    }

    /**
     * getAncestors: returns all objects in the roles hierarchy above a role
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return array of role objects. The objects can be queried with the getLevel() method to show their relationship (1=prents, 2=grandparents etc.).
     */
    public function getRoleAncestors()
    {
        // if this is the root return an empty array
        if ($this->getID() == 1) return array();
        // start by getting an array of the parents
        $parents = $this->getParents();
        $parents1 = array();
        foreach ($parents as $key => $parent) {
            $parents[$key]->setLevel(1);
        }
        //Reset the array pointer - else in some cases we may miss getting all ancestors
        reset($parents);
        // Get the parent field for each parent
        while (list($key, $parent) = each ($parents)) {
            $plevel = $parent->getLevel() + 1;
            $ancestors = $parent->getParents();
            foreach ($ancestors as $key1 => $ancestor) {
                $ancestors[$key1]->setLevel($plevel);
                $parents[] = $ancestors[$key1];
            }
        }

        $ancestors = array();
        // If this is a new ancestor add to the end of the array
        foreach ($parents as $parent) {
            $iscontained = false;
            foreach ($ancestors as $ancestor) {
                if ($parent->isEqual($ancestor)) {
                    $iscontained = true;
                    break;
                }
            }
            if (!$iscontained) $ancestors[] = $parent;
        }
        // done
        return $ancestors;
    }
    /**
     * getDescendants: get the descendaants of a group
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param int state get users in this state
     * @param int $grpflag
     * @return array list of users
     * @todo evaluate performance of this (3 loops, of which 2 nested)
     */
    public function getDescendants($state = xarRoles::ROLES_STATE_CURRENT, $grpflag=0)
    {
        $users = $this->getUsers($state);

        sys::import('modules.roles.class.roles');
        $groups = xarRoles::getSubGroups($this->getID());
        $ua = array();
        foreach($users as $user){
            //using the ID as the key so that if a person is in more than one sub group they only get one email (mrb: email?)
            $ua[$user->getID()] = $user;
        }
        //Get the sub groups and go for another round
        foreach($groups as $group){
            $role = xarRoles::get($group['id']);
            if ($grpflag) {
                $ua[$group['id']] = $role;
            }
            $users = $role->getDescendants($state);
            foreach($users as $user){
                $ua[$user->getID()] = $user;
            }
        }
        return($ua);
    }

    /**
     * isEqual: checks whether two roles are equal
     *
     * Two role objects are considered equal if they have the same id.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param object $role
     * @return boolean
     * @todo replace this with the hash object equality check?
     */
    public function isEqual($role)
    {
        return $this->getID() == $role->getID();
    }

    /**
     * isUser: checks whether this role is a user
     *
     * Users have itemtype = 1.
     * Groups have itemtype = 2.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return boolean
     */
    public function isUser()
    {
        return $this->getType() == xarRoles::ROLES_USERTYPE;
    }

    /**
     * isParent: checks whether a role is a parent of this one
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param object $role
     * @return boolean
     */
    public function isParent($role)
    {
        $parents = $this->getParents();
        foreach ($parents as $parent) {
            if ($role->isEqual($parent)) return true;
        }
        return false;
    }

    /**
     * isAncestor: checks whether a role is an ancestor of this one
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param object $role
     * @return boolean
     */
    public function isAncestor($role)
    {
        $ancestors = $this->getRoleAncestors();
        foreach ($ancestors as $ancestor) {
            if ($role->isEqual($ancestor)) return true;
        }
        return false;
    }

    /**
     * adjustParentUsers: adjust of a user's parent user tallies
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param int $adjust
     * @return boolean
     */
    public function adjustParentUsers($adjust)
    {
        xarMod::loadDbInfo('roles','roles');
        $xartable =& xarDB::getTables();
        $memberobject =  $this->rolestable;
        $bindvars = array();
        $query = "SELECT users AS users FROM $memberobject";
        $query1 = "UPDATE $memberobject ";

        $dbconn = xarDB::getConn();
        $parents = $this->getParents();
        foreach ($parents as $parent) {
            $query .= " WHERE id = ? ";
            $bindvars[] = $parent->getID();
            $query1 .= " WHERE id = ? ";
            $bindvars[] = $parent->getID();

            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
            if (!$result) return;
            // get the current count.
            while ($result->next())
            {
                $row = $result->fields;
            }

            $query1 .= " SET users = ? ";
            $value = $row['users'] + $adjust;
            $bindvars[] = $value;
            $stmt = $dbconn->prepareStatement($query1);
            $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
            if (!$result) return;
        }
        return true;
    }

    /**
     * Gets and Sets
     *
     * Get and set methods for the class variables
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @todo since there are so many a generalized getter (magic __get() ) might be more pleasurable
     */
    function getID() { return $this->properties['id']->value; }
    function getName() { return $this->properties['name']->getValue(); }
    function getUname() { return $this->properties['uname']->value; }
    function getType() { return $this->properties['role_type']->value; }
    function getUser() { return $this->properties['uname']->value; }
    function getEmail() { return $this->properties['email']->value; }
    function getPass() { return $this->properties['password']->value; }
    function getState() { return $this->properties['state']->value; }
    function getDateReg() { return $this->properties['regdate']->value; }
    function getValCode() { return $this->properties['valcode']->value; }
    function getAuthModule() { return $this->properties['authmodule']->value; }
    function getLevel()
    {
        return $this->parentlevel;
    }

    function setID($var) { $this->properties['id']->setValue($var); }
    function setName($var) { $this->properties['name']->setValue($var); }
    function setUname($var) { $this->properties['uname']->setValue($var); }
    function setType($var) { $this->properties['role_type']->setValue($var); }
    function setParent($var) { $this->properties['parentid']->setValue($var); }
    function setUser($var) { $this->properties['uname']->setValue($var); }
    function setEmail($var) { $this->properties['email']->setValue($var); }
    function setPass($var) { $this->properties['password']->setValue($var); }
    function setState($var) { $this->properties['state']->setValue($var); }
    function setDateReg($var) { $this->properties['regdate']->setValue($var); }
    function setValCode($var) { $this->properties['valcode']->setValue($var); }
    function setAuthModule($var) { $this->properties['authmodule']->setValue($var); }
    function setLevel($var)
    {
        $this->parentlevel = $var;
    }
}

sys::import('modules.dynamicdata.class.objects.list');

/**
 * RoleList: generic list class to handle getItems() etc. for roles
 *
 * Represents a list of roles (user or group)
 */
class RoleList extends DataObjectList
{
    public $visibility = 'private';

    // CHECKME: do we want anything special in here ?
}

?>
