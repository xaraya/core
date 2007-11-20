<?php
/**
 * xarPrivilege: class for the privileges object
 *
 * Represents a single privileges object
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
*/
sys::import('modules.privileges.class.mask');

class xarPrivilege extends xarMask
{
    public $parentid = 0;      //the id of the parent of this privilege

    /**
     * xarPrivilege: constructor for the class
     *
     * Just sets up the db connection and initializes some variables
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   array of values
     * @return  the privilege object
    */
    function __construct($pargs)
    {
        parent::__construct($pargs);
        $this->parentid     = isset($parentid) ? (int) $parentid : 0;
    }

    /**
     * add: add a new privileges object to the repository
     *
     * Creates an entry in the repository for a privileges object that has been created
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
    */
   function add()
   {
        if(empty($this->name)) {
            $msg = xarML('You must enter a name.','privileges');
            throw new DuplicateException(null,$msg);
            xarSession::setVar('errormsg', _MODARGSERROR);
            return false;
        }

        // create the insert query
        $realmid = null;
        if($this->realm != 'All') {
            $stmt = $this->dbconn->prepareStatement('SELECT id FROM '. $this->realmstable .' WHERE name=?');
            $result = $stmt->executeQuery(array($this->realm),ResultSet::FETCHMODE_ASSOC);
            if($result->next()) $realmid = $result->getInt('id');
        }
        $query = "INSERT INTO $this->privilegestable
                    (name, realm_id, module_id, component, instance, level, type, description)
                  VALUES (?,?,?,?,?,?,?,?)";
        $bindvars = array($this->name, $realmid, $this->module_id,
                          $this->component, $this->instance, $this->level, self::PRIVILEGES_PRIVILEGETYPE, $this->description);
        //Execute the query, bail if an exception was thrown
        $this->dbconn->Execute($query,$bindvars);
        // the insert created a new index value
        // retrieve the value
        $this->id = $this->dbconn->getLastId($this->privilegestable);

        // make this privilege a child of its parent
        if($this->parentid != 0) {
            sys::import('modules.privileges.class.privileges');
            $parentperm = xarPrivileges::getprivilege($this->parentid);
            $parentperm->addMember($this);
        }
        // create this privilege as an entry in the repository
        return $this->makeEntry();
    }

    /**
     * makeEntry: sets up a privilege without parents
     *
     * Sets up a privilege as a root entry (no parent)
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
     * @todo    check to make sure the child is not a parent of the parent
    */
    function makeEntry()
    {
        if ($this->isRootPrivilege()) return true;
        $query = "INSERT INTO $this->privmemberstable VALUES (?,?)";
        $this->dbconn->Execute($query,array($this->getID(),0));
        return true;
    }

    /**
     * addMember: adds a privilege to a privilege
     *
     * Make a privilege a member of another privilege.
     * A privilege can have any number of parents or children..
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   privilege object
     * @return  boolean
     * @todo    check to make sure the child is not a parent of the parent
    */
    function addMember($member)
    {
        $query = "INSERT INTO $this->privmemberstable VALUES (?,?)";
        $bindvars = array($member->getID(), $this->getID());
        //Execute the query, bail if an exception was thrown
        $this->dbconn->Execute($query,$bindvars);

// empty the privset cache
//        $privileges = new xarPrivileges();
//        $privileges->forgetprivsets();

        return true;
    }

    /**
     * removeMember: removes a privilege from a privilege
     *
     * Removes a privilege as an entry of another privilege.
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
    */
    function removeMember($member)
    {
        sys::import('modules.roles.class.xarQuery');
        $q = new xarQuery('SELECT', $this->privmemberstable, 'COUNT(*) AS count');
        $q->eq('id', $member->getID());
        if (!$q->run()) return;
        $total = $q->row();
        if($total['count'] == 0) return true;

        if($total['count'] > 1) {
            $q = new xarQuery('DELETE');
            $q->eq('parentid', $this->getID());
        } else {
            $q = new xarQuery('UPDATE');
            $q->addfield('parentid', 0);
        }
        $q->addtable($this->privmemberstable);
        $q->eq('id', $member->getID());
        if (!$q->run()) return;

// empty the privset cache
//        $privileges = new xarPrivileges();
//        $privileges->forgetprivsets();

        return true;
    }

    /**
     * update: updates a privilege in the repository
     *
     * Updates a privilege in the privileges repository
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
    */
    function update()
    {
        $realmid = null;
        if($this->realm != 'All') {
            $stmt = $this->dbconn->prepareStatement('SELECT id FROM '. $this->realmstable .' WHERE name=?');
            $result = $stmt->executeQuery(array($this->realm),ResultSet::FETCHMODE_ASSOC);
            if($result->next()) $realmid = $result->getInt('id');
        }

        $query =    "UPDATE " . $this->privilegestable .
                    ' SET name = ?,     realm_id = ?,
                          module_id = ?,   component = ?,
                          instance = ?, level = ?, type = ?
                      WHERE id = ?';
        $bindvars = array($this->name, $realmid, $this->module,
                          $this->component, $this->instance, $this->level, self::PRIVILEGES_PRIVILEGETYPE,
                          $this->getID());
        //Execute the query, bail if an exception was thrown
        $this->dbconn->Execute($query,$bindvars);
        return true;
    }

    /**
     * remove: deletes a privilege in the repository
     *
     * Deletes a privilege's entry in the privileges repository
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
     * @todo    reverse the order of deletion, i.e. first delete the related parts then the master (foreign key compat)
     * @todo    even better, do it in a transaction.
    */
    function remove()
    {

        // set up the DELETE query
        $query = "DELETE FROM $this->privilegestable WHERE id=?";
        //Execute the query, bail if an exception was thrown
        $this->dbconn->Execute($query,array($this->id));

        // set up a query to get all the parents of this child
        $query = "SELECT parentid FROM $this->privmemberstable
              WHERE id=?";
        //Execute the query, bail if an exception was thrown
        $stmt = $this->dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($this->getID()));

        // remove this child from all the parents
        while($result->next()) {
            list($parentid) = $result->fields;
            if ($parentid != 0) {
                $parentperm = xarPrivileges::getPrivilege($parentid);
                $parentperm->removeMember($this);
            }
        }

        // remove this child from the root privilege too
        $query = "DELETE FROM $this->privmemberstable WHERE id=? AND parentid=?";
        $stmt = $this->dbconn->prepareStatement($query);
        $stmt->executeUpdate(array($this->id,0));

        // get all the roles this privilege was assigned to
        $roles = $this->getRoles();
        // remove the role assignments for this privilege
        foreach ($roles as $role) {
            $this->removeRole($role);
        }

        // get all the child privileges
        $children = $this->getChildren();
        // remove the child privileges from this parent
        foreach ($children as $childperm) {
            $this->removeMember($childperm);
        }

        // CHECKME: re-assign all child privileges to the roles that the parent was assigned to ?

        return true;
    }

    /**
     * isassigned: check if the current privilege is assigned to a role
     *
     * This function looks at the acl table and returns true if the current privilege.
     * is assigned to a given role .
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   role object
     * @return  boolean
    */
    function isassigned($role)
    {
        static $stmt = null;

        $query = "SELECT partid FROM $this->acltable WHERE
                partid = ? AND permid = ?";
        $bindvars = array($role->getID(), $this->getID());
        if(!isset($stmt)) $stmt = $this->dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        return $result->first();
    }

    /**
     * getRoles: returns an array of roles
     *
     * Returns an array of roles this privilege is assigned to
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
     * @todo    seems to me this belong in roles module instead?
    */
    function getRoles()
    {
        // set up a query to select the roles this privilege
        // is linked to in the acl table
        $query = "SELECT r.id, r.name, r.type,
                         r.uname, r.email, r.pass,
                         r.auth_modid
                  FROM $this->rolestable r, $this->acltable acl
                  WHERE r.id = acl.partid AND
                        acl.permid = ?";
        $stmt = $this->dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($this->id));

        // make objects from the db entries retrieved
        sys::import('modules.roles.class.roles');
        $roles = array();
        //      $ind = 0;
    sys::import('modules.dynamicdata.class.objects.master');
        while($result->next()) {
            list($id,$name,$type,$uname,$email,$pass,$auth_modid) = $result->fields;
            //          $ind = $ind + 1;

            $role = DataObjectMaster::getObject(array('module' => 'roles', 'itemtype' => $type));
            $role->getItem(array('itemid' => $id));
            /*
            $role = new xarRole(array('id' => $id,
                                      'name' => $name,
                                      'type' => $type,
                                      'uname' => $uname,
                                      'email' => $email,
                                      'pass' => $pass,
                                      // NOTE: CHANGED since 1.x! to an id,
                                      // but i dont think it matters, auth module should probably
                                      // be phased out of this table completely
                                      'auth_module' => $auth_modid,
                                      'parentid' => 0));
            */
            $roles[] = $role;
        }
        // done
        return $roles;
    }

    /**
     * removeRole: removes a role
     *
     * Removes a role this privilege is assigned to
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   role object
     * @return  boolean
    */
    function removeRole($role)
    {
        // use the equivalent method from the roles object
        return $role->removePrivilege($this);
    }

    /**
     * getParents: returns the parent objects of a privilege
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  array of privilege objects
    */
    function getParents()
    {
        static $stmt = null;

        // create an array to hold the objects to be returned
        $parents = array();

        // perform a SELECT on the privmembers table
        $query = "SELECT p.*, m.name
                  FROM $this->privilegestable p INNER JOIN $this->privmemberstable pm ON p.id = pm.parentid
                  LEFT JOIN $this->modulestable m ON p.module_id = m.id
                  AND pm.id = ?";
        if(!isset($stmt)) $stmt = $this->dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($this->getID()));
        // collect the table values and use them to create new role objects
        while($result->next()) {
            list($id,$name,$realm,$module_id,$component,$instance,$level,$description,$module) = $result->fields;
            $pargs = array('id'=>$id,
                            'name'=>$name,
                            'realm'=>$realm,
                            'module'=>$module,
                            'module_id'=>$module_id,
                            'component'=>$component,
                            'instance'=>$instance,
                            'level'=>$level,
                            'description'=>$description,
                            'parentid' => $id);
            array_push($parents, new xarPrivilege($pargs));
        }
        // done
        return $parents;
    }

    /**
     * getAncestors: returns all objects in the privileges hierarchy above a privilege
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  array of privilege objects
    */
    function getAncestors()
    {
        // if this is the root return an empty array
        if ($this->getID() == 1) return array();

        // start by getting an array of the parents
        $parents = $this->getParents();

        //Get the parent field for each parent
        while (list($key, $parent) = each($parents)) {
            $ancestors = $parent->getParents();
            foreach ($ancestors as $ancestor) {
                array_push($parents,$ancestor);
            }
        }

        //done
        $ancestors = array();
        $parents = array_merge($ancestors,$parents);
        return $ancestors;
    }

    /**
     * getChildren: returns the child objects of a privilege
     *
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  array of privilege objects
    */
    function getChildren()
    {
        $cacheId = $this->getID();

        // we retrieve and cache everything at once now
        if (xarVarIsCached('Privileges.getChildren', 'cached')) {
            if (xarVarIsCached('Privileges.getChildren', $cacheId)) {
                return xarVarGetCached('Privileges.getChildren', $cacheId);
            } else {
                return array();
            }
        }

        // create an array to hold the objects to be returned
        $children = array();

        $query = "SELECT p.*, pm.parentid, m.name
                    FROM $this->privilegestable p INNER JOIN $this->privmemberstable pm ON p.id = pm.id
                    LEFT JOIN $this->modulestable m ON p.module_id = m.id
                    WHERE p.id = pm.id";
        // retrieve all children of everyone at once
        //              AND pm.parentid = " . $cacheId;
        // Can't use caching here. The privs have changed
        $result = $this->dbconn->executeQuery($query);

        while($result->next()) {
            list($id,$name,$realm,$module_id,$component,$instance,$level,$description,$type,$parentid, $module) = $result->fields;
            if (!isset($children[$parentid])) $children[$parentid] = array();
            $pargs = array('id'=>           $id,
                            'name'=>        $name,
                            'realm'=>       $realm,
                            'module_id'=>   $module_id,
                            'module'=>      $module,
                            'component'=>   $component,
                            'instance'=>    $instance,
                            'level'=>       $level,
                            'description'=> $description,
                            'parentid' => $parentid);
            $children[$parentid][] = new xarPrivilege($pargs);
        }
        // done
        foreach (array_keys($children) as $parentid) {
            xarVarSetCached('Privileges.getChildren', $parentid, $children[$parentid]);
        }
        xarVarSetCached('Privileges.getChildren', 'cached', 1);
        if (isset($children[$cacheId])) {
            return $children[$cacheId];
        } else {
            return array();
        }
    }

    /**
     * getDescendants: returns all objects in the privileges hierarchy below a privilege
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  array of privilege objects
    */
    function getDescendants()
    {
        // start by getting an array of the parents
        $children = $this->getChildren();

        //Get the child field for each child
        while (list($key, $child) = each($children)) {
            $descendants = $child->getChildren();
            foreach ($descendants as $descendant) {
                $children[] =$descendant;
            }
        }

        //done
        $descendants = array();
        $descendants = array_merge($descendants,$children);
        return $descendants;
    }

    /**
     * isEqual: checks whether two privileges are equal
     *
     * Two privilege objects are considered equal if they have the same id.
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   Object $privilege ???
     * @return  boolean
    */
    function isEqual($privilege)
    {
        return $this->getID() == $privilege->getID();
    }

    /**
     * getID: returns the ID of this privilege
     *
     * This overrides the method of the same name in the parent class
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
    */
    function getID()
    {
        return $this->id;
    }

    /**
     * isEmpty: returns the type of this privilege
     *
     * This methods returns true if the privilege is an empty container
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
    */
    function isEmpty()
    {
        return $this->module == null;
    }

    /**
     * isParentPrivilege: checks whether a given privilege is a parent of this privilege
     *
     * This methods returns true if the privilege is a parent of this one
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   Object $privileg ???
     * @return  boolean
    */
    function isParentPrivilege($privilege)
    {
        $privs = $this->getParents();
        foreach ($privs as $priv) {
            if ($privilege->isEqual($priv)) return true;
        }
        return false;
    }

    /**
     * isRootPrivilege: checks whether this privilege is root privilege
     *
     * This methods returns true if this privilege is a root privilege
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
    */
    function isRootPrivilege()
    {
        sys::import('modules.roles.class.xarQuery');
        $q = new xarQuery('SELECT');
        $q->addtable($this->privilegestable,'p');
        $q->addtable($this->privmemberstable,'pm');
        $q->join('p.id','pm.id');
        $q->eq('pm.id',$this->getID());
        $q->eq('pm.parentid',0);
        if(!$q->run()) return;
        return ($q->output() != array());
    }
}
?>
