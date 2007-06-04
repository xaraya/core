<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */

sys::import('modules.dynamicdata.class.objects.base');
sys::import('modules.roles.class.xarQuery');
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
    public $basetype;     //the base itemtype. we add this so it can be passed rather than calculated here

    public $dbconn;
    public $rolestable;
    public $rolememberstable;
    public $privilegestable;
    public $acltable;
    public $realmstable;

    public $allprivileges;

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
        $args = $descriptor->getArgs();
        extract($args);

        $this->dbconn = xarDB::getConn();

        // dodgy. remove later on
        sys::import('modules.privileges.xartables');
        xarDB::importTables(privileges_xartables());

        $xartable = xarDB::getTables();
        $this->rolestable = $xartable['roles'];
        $this->rolememberstable = $xartable['rolemembers'];
        $this->privilegestable = $xartable['privileges'];
        $this->acltable = $xartable['security_acl'];
        $this->realmstable = $xartable['security_realms'];

        $this->parentlevel = 0;
        $ancestor = $this->getBaseAncestor();
        $this->basetype = $ancestor['itemtype'];
    }

    /**
     * createItem: add a new role item to the repository
     *
     * Creates an entry in the repository for a role object that has been created
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return bool
     */
    public function createItem(Array $data = array())
    {
        // Confirm that this group or user does not already exist
        $q = new xarQuery('SELECT',$this->rolestable);
        if ($this->basetype == ROLES_GROUPTYPE) {
            if (empty($data['name'])) $data['name'] = $this->getName();
            $q->eq('name',$data['name']);
        } else {
            if (empty($data['uname'])) $data['uname'] = $this->getUser();
            $q->eq('uname',$data['uname']);
        }

        if (!$q->run()) return;

        if ($q->getrows() > 0) {
            throw new DuplicateException(array('role',($this->basetype == ROLES_GROUPTYPE)?$this->getName():$this->getUser()));
        }

        $id = parent::createItem($data);

        // Set the email useage for this user to false
        xarModSetUserVar('roles','allowemail', false, $id);

        // Get a value for the parent id
        if (empty($data['parentid'])) xarVarFetch('parentid',  'int', $data['parentid'],  NULL, XARVAR_DONT_SET);
        if (empty($data['parentid'])) $data['parentid'] = xarModVars::get('roles', 'defaultgroup');
        if (!empty($data['parentid'])) {
            $parent = xarRoles::get($data['parentid']);
            if (!$parent->addMember($this))
                throw new Exception('Unable to create a roles relation');
        }

        // add the duvs
        if (!xarVarFetch('duvs','array',$duvs,array(),XARVAR_NOT_REQUIRED)) return;
        foreach($duvs as $key => $value) {
            xarModSetUserVar('roles',$key, $value, $id);
        }

        // Let any hooks know that we have created a new user.
        $item['module'] = 'roles';
        $item['itemtype'] = $this->getType();
        $item['itemid'] = $id;
        xarModCallHooks('item', 'create', $id, $item);

        return $id;
    }

    public function updateItem(Array $data = array())
    {
        $id = parent::updateItem($data);
        if (!xarVarFetch('duvs','array',$duvs,array(),XARVAR_NOT_REQUIRED)) return;
        foreach($duvs as $key => $value) {
            xarModSetUserVar('roles',$key, $value, $id);
        }
        $item['module'] = 'roles';
        $item['itemtype'] = $this->getType();
        $item['itemid'] = $id;
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
     * @return bool
     */
    public function addMember($member)
    {
        // bail if the purported parent is not a group.
        if ($this->isUser()) return false;

        $q = new xarQuery('SELECT',$this->rolememberstable);
        $q->eq('id',$member->getID());
        $q->eq('parentid',$this->getID());
        if (!$q->run()) return;
        // This relationship already exists. Move on
        if ($q->row() != array()) return true;

        // add the necessary entry to the rolemembers table
        $q = new xarQuery('INSERT',$this->rolememberstable);
        $q->addfield('id',$member->getID());
        $q->addfield('parentid',$this->getID());
        if (!$q->run()) return;

        // for children that are users
        // add 1 to the users field of the parent group. This is for display purposes.
        if ($member->isUser()) {
            // get the current count
            $q = new xarQuery('SELECT',$this->rolestable,'users');
            $q->eq('id',$this->getID());
            if (!$q->run()) return;
            $result = $q->row();

            // add 1 and update.
            $q = new xarQuery('UPDATE',$this->rolestable);
            $q->eq('id',$this->getID());
            $q->addfield('users',$result['users']+1);
            if (!$q->run()) return;
        }
        $item['module']   = 'roles';
        $item['itemtype'] = $this->getType();
        $item['itemid']   = $this->getID();
        xarModCallHooks('item', 'link', $this->getID(), $item);
        return true;
    }

    /**
     * removeMember: removes a role from a group
     *
     * Removes a user or group as an entry of another group.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param object $member
     * @return bool
     * @todo add transaction around the delete and the update
     */
    public function removeMember($member)
    {
        // delete the relevant entry from the rolemembers table
        $query = "DELETE FROM $this->rolememberstable WHERE id= ? AND parentid= ?";
        $bindvars = array($member->getID(), $this->getID());
        $this->dbconn->Execute($query,$bindvars);
        // for children that are users
        // subtract 1 from the users field of the parent group. This is for display purposes.
        if ($member->isUser()) {
            // get the current count.
            $q = new xarQuery('SELECT',$this->rolestable,'users');
            $q->eq('id',$this->getID());
            if (!$q->run()) return;
            $result = $q->row();

            // subtract 1 and update.
            $q = new xarQuery('UPDATE',$this->rolestable);
            $q->eq('id',$this->getID());
            $q->addfield('users',$result['users']-1);
            if (!$q->run()) return;
        }
        $item['module']   = 'roles';
        $item['itemtype'] = $this->getType();
        $item['itemid']   = $this->getID();
        xarModCallHooks('item', 'unlink', $this->getID(), $item);
        return true;
    }

    /**
     * deleteItem: make a role deleted
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return bool
     * @todo flag illegal deletes
     */
    public function deleteItem(Array $data = array())
    {
        if (!empty($data['itemid'])) $this->setID($data['itemid']);

        // FIXME: park this here for the moment
        if($this->getID() == xarModVars::get('roles','defaultgroup'))
            throw new ForbiddenOperationException($defaultgroup,'The group #(1) is the default group for new users. If you want to remove it change the appropriate configuration setting first.');

        // get a list of all relevant entries in the rolemembers table
        // where this role is the child
        $query = "SELECT parentid FROM $this->rolememberstable WHERE id= ?";
        // Execute the query, bail if an exception was thrown
        $stmt = $this->dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($this->getID()));

        // FIXME: park this here for the moment
        if(count($result->fields) == 1)
            throw new ForbiddenOperationException(null,'The user has one parent group, removal is not allowed');

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

        //Let's not remove the role yet.  Instead, we want to deactivate it
        // <mrb> i'm not a fan of the name munging
        $deleted = xarML('deleted');
        $args = array(
            'user' => "[" . $deleted . "]" . time(),
            'email' => "[" . $deleted . "]" . time(),
            'state' => ROLES_STATE_DELETED,
        );
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
        xarModCallHooks('item', 'delete', $this->getID(), $item);

        // CHECKME: re-assign all privileges to the child roles ? (probably not)
        return true;
    }


    /**
     * purge: make a role purged
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return bool
     */
    public function purge()
    {
        // no checks here. just do it
        $this->deleteItem();
        $state = ROLES_STATE_DELETED;
        $uname = xarML('deleted') . microtime(TRUE) .'.'. $this->properties['id']->value;
        $name = '';
        $pass = '';
        $email = '';
        $date_reg = '';
        $q = new xarQuery('UPDATE',$this->rolestable);
        $q->addfield('name',$name);
        $q->addfield('uname',$uname);
        $q->addfield('pass',$pass);
        $q->addfield('email',$email);
        $q->addfield('date_reg',$date_reg);
        $q->addfield('state',$state);
        $q->eq('id',$this->getID());
        if(!$q->run()) return;
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
        static $stmt = null;  // For each uid, the query is the same, prepare it once.

        $cacheKey = "Privileges.ByUid";
        if(xarVarIsCached($cacheKey,$this->properties['id']->value)) {
            return xarVarGetCached($cacheKey,$this->properties['id']->value);
        }
        // We'll have to get it.
        xarLogMessage("ROLE: getting privileges for uid: $this->properties['id']->value");
        // TODO: propagate the use of 'All'=null for realms through the API instead of the flip-flopping
        $xartable = xarDB::getTables();
        $query = "SELECT  p.id, p.name, r.name, p.module_id,
                          component, instance, level, description
                  FROM    $this->acltable acl,
                          $this->privilegestable p LEFT JOIN $this->realmstable r ON p.realmid = r.id
                  WHERE   p.id = acl.permid AND
                          acl.partid = ?";
//                          echo $query;exit;
        if(!isset($stmt)) $stmt = $this->dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($this->properties['id']->value));

        sys::import('modules.privileges.class.privilege');
        $privileges = array();
        while ($result->next()) {
            list($id, $name, $realm, $module_id, $component, $instance, $level,
                $description) = $result->fields;
            $perm = new xarPrivilege(array('id' => $id,
                    'name' => $name,
                    'realm' => is_null($realm) ? 'All' : $realm,
                    'module' => $module_id,
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
     * @return bool
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
     * @return bool
     */
    public function assignPrivilege($privilege)
    {
        // create an entry in the privmembers table
        $query = "INSERT INTO $this->acltable VALUES (?,?)";
        $bindvars = array($this->getID(),$privilege->getID());
        $this->dbconn->Execute($query,$bindvars);
        return true;
    }

    /**
     * removePrivilege: removes a privilege from a role
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param object $privilege
     * @return bool
     */
    public function removePrivilege($privilege)
    {
        // remove an entry from the privmembers table
        $query = "DELETE FROM $this->acltable
                  WHERE partid= ? AND permid= ?";
        $bindvars = array($this->properties['id']->value, $privilege->getID());
        $this->dbconn->Execute($query,$bindvars);
        return true;
    }

    /**
     * getUsers: get the members of a group that are users
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param integer state get users in this state
     * @param integer startnum get users beyond this number
     * @param integer numitems get a defined number of users
     * @param string order order the result (name, uname, type, email, date_reg, state...)
     * @param string selection get users within this selection criteria
     * @return array
     */
    public function getUsers($state = ROLES_STATE_CURRENT, $startnum = 0, $numitems = 0, $order = 'name', $selection = NULL)
    {
        $query = "SELECT r.id, r.name, r.type, r.uname,
                         r.email, r.pass, r.date_reg,
                         r.valcode, r.state,r.auth_modid
                  FROM $this->rolestable r, $this->rolememberstable rm
                  WHERE r.id = rm.id AND
                        r.type = ? AND
                        r.state != ? AND
                        rm.parentid = ?";
        // set up the query and get the data
        if ($state == ROLES_STATE_CURRENT) {
             $bindvars = array(ROLES_USERTYPE,ROLES_STATE_DELETED,$this->getID());

        } else {
             $bindvars = array(ROLES_USERTYPE, $state, $this->properties['id']->value);
        }
        if (isset($selection)) $query .= $selection;
        $query .= " ORDER BY " . $order;
        // Prepare the query
        $stmt = $this->dbconn->prepareStatement($query);

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
            list($uid) = $result->fields;

            $role = DataObjectMaster::getObject(array('name' => 'roles_users'));
            $role->getItem(array('itemid' => $uid));
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
     * @param integer type group or user
     * @return int
     */
    public function countChildren($state = ROLES_STATE_CURRENT, $selection = NULL, $type = NULL)
    {
        $q = new xarQuery('SELECT');
        $q->addfield('COUNT(r.id) AS children');
        $q->addtable($this->rolestable,'r');
        $q->addtable($this->rolememberstable,'rm');
        $q->join('r.id', 'rm.id');
        $q->eq('rm.parentid', $this->properties['id']->value);
        if ($state == ROLES_STATE_CURRENT) {
            $q->ne('r.state', ROLES_STATE_DELETED);
        } else {
            $q->eq('r.state', $state);
        }
        if (isset($type)) $q->eq('r.type', $type);

        if (isset($selection)) {
            $query = $q->tostring() . $selection;
            if(!$q->run($query)) return;
        } else {
            if(!$q->run()) return;
        }
        $result = $q->row();
        return $result['children'];
    }

    /**
     * countUsers: count the members of a group that are users
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param int    $state count user in this state
     * @param string $selection count user within this selection criteria
     * @return int
     */
    public function countUsers($state = ROLES_STATE_CURRENT, $selection = NULL)
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
        static $stmt = null;  // The query below is the same for each uid, prepare it once.

        $cacheKey = 'RoleParents.ByUid';
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
                  WHERE r.id = rm.parentid AND rm.id = ?";
        if(!isset($stmt)) $stmt = $this->dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($this->properties['id']->value));

        // collect the table values and use them to create new role objects
        while ($result->next()) {
            list($uid) = $result->fields;

            $role = DataObjectMaster::getObject(array('name' => 'roles_groups'));
            $role->getItem(array('itemid' => $uid));
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
    public function getDescendants($state = ROLES_STATE_CURRENT, $grpflag=0)
    {
        $users = $this->getUsers($state);

        $groups = xarRoles::getSubGroups($this->getID());
        $ua = array();
        foreach($users as $user){
            //using the ID as the key so that if a person is in more than one sub group they only get one email (mrb: email?)
            $ua[$user->getID()] = $user;
        }
        //Get the sub groups and go for another round
        foreach($groups as $group){
            $role = xarRoles::get($group['uid']);
            if ($grpflag) {
                $ua[$group['uid']] = $role;
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
     * Two role objects are considered equal if they have the same uid.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param object $role
     * @return bool
     * @todo replace this with the hash object equality check?
     */
    public function isEqual($role)
    {
        return $this->getID() == $role->getID();
    }

    /**
     * isUser: checks whether this role is a user
     *
     * Users have type = 2.
     * Groups have type = 3.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return bool
     */
    public function isUser()
    {
        $base = xarModAPIFunc('dynamicdata','user','getbaseancestor',array('itemtype' => $this->getType(), 'moduleid' => 27));
        return $base['itemtype'] == ROLES_USERTYPE;
    }

    /**
     * isParent: checks whether a role is a parent of this one
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param object $role
     * @return bool
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
     * @return bool
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
     * @return bool
     */
    public function adjustParentUsers($adjust)
    {
        $q = new xarQuery('SELECT', $this->rolestable, 'users AS users');
        $q1 = new xarQuery('UPDATE', $this->rolestable);
        $parents = $this->getParents();
        foreach ($parents as $parent) {
            $q->clearconditions();
            $q->eq('id', $parent->getID());
            $q1->clearconditions();
            $q1->eq('id', $parent->getID());

            // get the current count.
            if (!$q->run()) return;
            $row = $q->row();

            // adjust and update update.
            $q1->addfield('users', $row['users'] + $adjust);
            if (!$q1->run()) return;
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
    function getName() { return $this->properties['name']->value; }
    function getUname() { return $this->properties['name']->value; }
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

    function setName($var) { $this->properties['name']->value = $var; }
    function setUname($var) { $this->properties['name']->value = $var; }
    function setParent($var) { $this->properties['parentid']->value = $var; }
    function setUser($var) { $this->properties['uname']->value = $var; }
    function setEmail($var) { $this->properties['email']->value = $var; }
    function setPass($var) { $this->properties['password']->value = $var; }
    function setState($var) { $this->properties['state']->value = $var; }
    function setDateReg($var) { $this->properties['datereg']->value = $var; }
    function setValCode($var) { $this->properties['valcode']->value = $var; }
    function setAuthModule($var) { $this->properties['authmodule']->value = $var; }
    function setLevel($var)
    {
        $this->parentlevel = $var;
    }
}
?>
