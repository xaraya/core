<?php
/**
 * xarRoles class
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */

/** Marc: we *really* need to discuss this, wrt performance and bind variables */
if(!class_exists('xarQuery')) include dirname(__FILE__).'/xarincludes/xarQuery.php';

define('ROLES_STATE_DELETED',0);
define('ROLES_STATE_INACTIVE',1);
define('ROLES_STATE_NOTVALIDATED',2);
define('ROLES_STATE_ACTIVE',3);
define('ROLES_STATE_PENDING',4);
define('ROLES_STATE_CURRENT',98);
define('ROLES_STATE_ALL',99);

define('ROLES_ROLETYPE',1);
define('ROLES_USERTYPE',2);
define('ROLES_GROUPTYPE',3);

/**
 * xarRoles: class for the role repository
 *
 * Represents the repository containing all roles
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @throws none
 * @todo none
 */
class xarRoles
{
    public $allgroups = array();
    public $users = array();
    public $dbconn;
    public $rolestable;
    public $rolememberstable;

    function __construct()
    {
        $this->dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();
        $this->rolestable = $xartable['roles'];
        $this->rolememberstable = $xartable['rolemembers'];
    }

    /**
     * getgroups: returns all the current groups.
     *
     * Returns an array of all the groups in the roles repository
     * The repository contains an entry for each user and group.
     * This function will initially load the groups from the db into an array and return it.
     * On subsequent calls it just returns the array .
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @param none $
     * @return array of arrays representing all the groups
     * @throws none
     * @todo none
     */
    function getgroups()
    {
        static $allgroups = array();
        if (empty($allgroups)) {
            $q = $this->_getgroupsquery();
            if (!$q->run()) return;
            $allgroups = $q->output();
        }
        return $allgroups;
    }

    /**
     * getgroup: returns an array representing a group
     *
     * Returns an array of representing a group in the roles repository
     * The repository contains an entry for each user and group.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @param integer $
     * @return array representing the group
     * @throws none
     * @todo none
     */
    function getgroup($uid)
    {
        $q = $this->_getgroupsquery();
        $q->eq('r.xar_uid',$uid);
        if (!$q->run()) return;
        if ($q->row() != array()) return $q->row();
        return false;
    }

    /**
     * _getgroupsquery: query for getting groups
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @todo none
     */
    private function _getgroupsquery()
    {
        $types = xarModAPIFunc('dynamicdata','user','getmoduleitemtypes',array('moduleid' => 27));
        $basetypes = array();
        foreach ($types as $key => $value) {
            $basetype = xarModAPIFunc('dynamicdata','user','getbaseancestor',array('itemtype' => $key, 'moduleid' => 27));
            if ($basetype['itemtype'] == ROLES_GROUPTYPE) $basetypes[] = $key;
        }
        // set up the query and get the groups
        $q = new xarQuery('SELECT');
        $q->addtable($this->rolestable,'r');
        $q->addtable($this->rolememberstable,'rm');
        $q->join('r.xar_uid','rm.xar_uid');
        $q->addfield('r.xar_uid AS uid');
        $q->addfield('r.xar_name AS name');
        $q->addfield('r.xar_users AS users');
        $q->addfield('rm.xar_parentid AS parentid');
        $c = array();
        foreach ($basetypes as $type) {
            $c[] = $q->eq('r.xar_type',$type);
        }
        $q->qor($c);
        $q->eq('r.xar_state',ROLES_STATE_ACTIVE);
        $q->setorder('r.xar_name');
        return $q;
    }

    /**
     * getsubgroups: get the children of a group that are groups themselves
     *
     * This function is useful for setting up trees
     * We don't include users in the tree because there are too many to display
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @param none $
     * @return array representing the subgroups of a group
     * @throws none
     * @todo none
     */
    function getsubgroups($uid)
    {
        $subgroups = array();
        $groups = $this->getgroups();
        foreach($groups as $subgroup) {
            if ($subgroup['parentid'] == $uid) {
                $subgroups[] = $subgroup;
            }
        }
        return $subgroups;
    }

    /**
     * getRole: gets a single role
     *
     * Retrieves a single role (user or group) from the roles repository
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param integer $
     * @return role object
     * @throws none
     * @todo none
     */
    function getRole($uid)
    {
        $cacheKey = 'Roles.ByUid';
        if(xarVarIsCached($cacheKey,$uid)) {
            return xarVarGetCached($cacheKey,$uid);
        }
        // Need to get it from DB.
        // TODO: move caching to _lookuprole?
        $r = $this->_lookuprole('xar_uid',(int) $uid);
        xarVarSetCached($cacheKey,$uid,$r);
        return $r;
    }

    /**
     * findRole: finds a single role based on its name
     *
     * Retrieves a single role object from the Roles repository
     * This is a convenience class for module developers
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param string $
     * @return role object
     * @throws none
     * @todo cache this too?
     */
    function findRole($name)
    {
        return $this->_lookuprole('xar_name',$name);
    }

    function ufindRole($uname)
    {
        return $this->_lookuprole('xar_uname',$uname);
    }

    private function _lookuprole($field,$value,$state=ROLES_STATE_ALL)
    {
        // retrieve the object's data from the repository
        // set up and execute the query
        $q = new xarQuery('SELECT',$this->rolestable);
        $q->eq($field,$value);
        if ($state == ROLES_STATE_CURRENT) {
            $q->ne('xar_state',ROLES_STATE_DELETED);
        } elseif ($state != ROLES_STATE_ALL) {
            $q->eq('xar_state',$state);
        }

        // Execute the query, bail if an exception was thrown
        if (!$q->run()) return;

        // set the data in an array
        $row = $q->row();
        if (empty($row)) return;

        $duvarray = array('userhome','primaryparent','passwordupdate','timezone');
        $duvs = array();
        foreach ($duvarray as $key) {
            if (xarModGetVar('roles',$key)) {
                $duvs[$key] = xarModGetUserVar('roles',$key,$row['xar_uid']);
                $duvs[$key] = ($duvs[$key] == 1) ? '' : $duv;
            }
        }
        $pargs = array(
            'uid' =>         $row['xar_uid'],
            'name' =>        $row['xar_name'],
            'type' =>        $row['xar_type'],
            'users' =>       $row['xar_users'],
            'uname' =>       $row['xar_uname'],
            'email' =>       $row['xar_email'],
            'pass' =>        $row['xar_pass'],
            'date_reg' =>    $row['xar_date_reg'],
            'val_code' =>    $row['xar_valcode'],
            'state' =>       $row['xar_state'],
            'auth_module' => $row['xar_auth_modid'],
            'duvs'          => $duvs    );
        // create and return the role object
        return new xarRole($pargs);
    }

    /**
     * makeMemberByName: makes a role a child of a group
     *
     * Creates an entry in the rolemembers table
     * This is a convenience class for module developers
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param string $
     * @param string $
     * @return boolean
     * @throws none
     * @todo create exceptions for bad input
     * @todo seems we could do this in one query instead of two?
     */
    function makeMemberByName($childname, $parentname)
    {
        // retrieve the parent's data from the repository
        $query = "SELECT * FROM $this->rolestable WHERE xar_name = ?";
        // Prepare it once
        $stmt = $this->dbconn->prepareStatement($query);

        // Execute the query, bail if an exception was thrown
        $result = $stmt->executeQuery(array($parentname));
        $result->first();

        // create the parent object
        list($uid, $name, $type, $parentid, $uname, $email, $pass,
            $date_reg, $val_code, $state, $auth_module) = $result->fields;
        $pargs = array('uid' => $uid,
                       'name' => $name,
                       'type' => $type,
                       'parentid' => $parentid,
                       'uname' => $uname,
                       'email' => $email,
                       'pass' => $pass,
                       'date_reg' => $date_reg,
                       'val_code' => $val_code,
                       'state' => $state,
                       'auth_module' => $auth_module);
        $parent = new xarRole($pargs);
        // retrieve the child's data from the repository
        // Execute the query, bail if an exception was thrown
        $result = $stmt->executeQuery(array($childname));
        $result->first();

        // create the child object
        list($uid, $name, $type, $parentid, $uname, $email, $pass,
            $date_reg, $val_code, $state, $auth_module) = $result->fields;
        $pargs = array('uid' => $uid,
                       'name' => $name,
                       'type' => $type,
                       'parentid' => $parentid,
                       'uname' => $uname,
                       'email' => $email,
                       'pass' => $pass,
                       'date_reg' => $date_reg,
                       'val_code' => $val_code,
                       'state' => $state,
                       'auth_module' => $auth_module);
        $child = new xarRole($pargs);
       // done
        return $parent->addMember($child);
    }

    /**
     * isRoot: defines the root of the roles hierarchy
     *
     * Creates an entry in the rolemembers table
     * This is a convenience class for module developers
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param string $
     * @return boolean
     * @throws none
     * @todo create exceptions for bad input
     */
    function isRoot($rootname)
    {
        // get the data for the root object
        $query = "SELECT xar_uid
                  FROM $this->rolestable
                  WHERE xar_name = ?";
        // Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($rootname));

        // create the entry
        list($uid) = $result->fields;
        $query = "INSERT INTO $this->rolememberstable
                VALUES (?,?)";
        // Execute the query, bail if an exception was thrown
        $this->dbconn->Execute($query, array($uid,0));
        // done
        return true;
    }

    /**
     * makeUser: add a new role object to the repository
     *
     * Creates an entry in the repository for a role object that has been created
     * This is a convenience method for module developers
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param string $
     * @param string $
     * @param string $
     * @return boolean
     * @throws none
     * @todo create exception handling for bad input
     */
    function makeUser($name, $uname, $email, $pass = 'xaraya', $datereg = '', $valcode = '', $state = ROLES_STATE_ACTIVE, $authmodule = '', $duvs=array())
    {
        // TODO: validate the email address
        if (empty($name) && empty($uname) || empty($email)) {
            $msg = 'You must enter a user name and a valid email address.';
            throw new EmptyParameterException(null,$msg);
        }
        // Confirm that this group or user does not already exist
        $q = new xarQuery('SELECT',$this->rolestable);
        $q->eq('xar_uname',$uname);

        if (!$q->run()) return;
        if ($q->getrows() > 0)
            throw new DuplicateException(array('user',$uname));

        // create an ID for the user
        $nextId = $this->dbconn->genID($this->rolestable);

        // set up the query and create the entry
        $tablefields = array(
            array('name' => 'xar_uid',         'value' => $nextId),
            array('name' => 'xar_name',        'value' => $name),
            array('name' => 'xar_type',        'value' => ROLES_USERTYPE),
            array('name' => 'xar_uname',       'value' => $uname),
            array('name' => 'xar_email',       'value' => $email),
            array('name' => 'xar_pass',        'value' => $pass),
            array('name' => 'xar_date_reg',    'value' => time()),
            array('name' => 'xar_valcode',     'value' => $valcode),
            array('name' => 'xar_state',       'value' => $state),
            array('name' => 'xar_auth_modid', 'value' => $authmodule),
        );
        $q = new xarQuery('INSERT',$this->rolestable);
        $q->addfields($tablefields);
        if (!$q->run()) return;
        foreach($duvs as $key => $value) xarModUserSetVar($key,$nextId,$value);
        // done
        return true;
    }
    /**
     * makeGroup: add a new role object to the repository
     *
     * Creates an entry in the repository for a role object that has been created
     * This is a convenience method for module developers
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param string $
     * @return boolean
     * @throws none
     */
    function makeGroup($name,$uname='')
    {
        if ($uname == '') $uname = $name;

        // Confirm that this group or user does not already exist
        $q = new xarQuery('SELECT',$this->rolestable,'COUNT(*)');
        $q->eq('xar_name',$name);
        $q->ne('xar_state',ROLES_STATE_DELETED);
        if (!$q->run()) return;

        $row = $q->row();
        if ($row['COUNT(*)'] > 0) {
            throw new DuplicateException(array('group',$name));
            return false;
        }

        $createdate = time();
        $query = "INSERT INTO $this->rolestable
                    (xar_uid, xar_name, xar_type, xar_uname,xar_date_reg)
                  VALUES (?,?,?,?,?)";
        $bindvars = array($this->dbconn->genID($this->rolestable),
                          $name, ROLES_GROUPTYPE, $uname, $createdate);
        $this->dbconn->Execute($query,$bindvars);
        // done
        return true;
    }
}

/**
 * xarRole: class for the role object
 *
 * Represents a single role (user or group)
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @throws none
 * @todo none
 */
class xarRole
{
    public $uid;          //the id of this user or group
    public $name;         //the name of this user or group
    public $type;         //the type of this role (0=user, 1=group)
    public $parentid;     //the id of the parent of this role
    public $uname;        //the user name (not used by groups)
    public $email;        //the email address (not used by groups)
    public $pass;         //the password (not used by groups)
    public $date_reg;     //the date of registration
    public $val_code;     //the validation code of this user or group
    public $state;        //the state of this user or group
    public $auth_module;  //no idea what this is (not used by groups)
    public $duvs;         //property for holding dynamic user vars
    public $parentlevel;  //we use this just to store transient information
    public $basetype;     //the base itemtype. we add this so it can be passed rather than calculated here

    public $dbconn;
    public $rolestable;
    public $rolememberstable;
    public $privilegestable;
    public $acltable;

    public $allprivileges;

    /**
     * xarRole: constructor for the role object
     *
     * Retrieves a single role (user or group) from the roles repository
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param array $
     * @return role object
     * @throws none
     * @todo none
     */
    function __construct($pargs)
    {
        extract($pargs);

        $this->dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();
        $this->rolestable = $xartable['roles'];
        $this->rolememberstable = $xartable['rolemembers'];
        $this->privilegestable = $xartable['privileges'];
        $this->acltable = $xartable['security_acl'];

        if (!isset($uid)) $uid = 0;
        if (!isset($type)) $type = ROLES_USERTYPE;
        if (!isset($parentid)) $parentid = 1;
        if (!isset($uname)) $uname = xarSessionGetVar('uid') . microtime();
        usleep(1);// <-- Huh? why?
        if (!isset($email)) $email = '';
        if (!isset($pass)) $pass = '';
        if (!isset($state)) $state = ROLES_STATE_INACTIVE;
        // FIXME: why is date_reg a varchar in the database and not a date field?
        if (!isset($date_reg)) $date_reg = time();
        if (!isset($val_code)) $val_code = 'createdbyadmin';
        // FIXME: what is a sensible default for auth_module?
        if (!isset($auth_module)) $auth_module = 0;
        if (!isset($basetype)) $basetype = 0;

        $this->uid = (int) $uid;
        $this->name = $name;
        $this->type = (int) $type;
        $this->parentid = (int) $parentid;
        $this->uname = $uname;
        $this->email = $email;
        $this->pass = $pass;
        $this->state = (int) $state;
        $this->date_reg = $date_reg;
        $this->val_code = $val_code;
        $this->auth_module = $auth_module;
        $this->parentlevel = 0;
        $this->duvs = isset($duvs) ? $duvs : array();
        $this->basetype = $basetype;
    }

    /**
     * add: add a new role object to the repository
     *
     * Creates an entry in the repository for a role object that has been created
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return boolean
     * @throws none
     * @todo none
     */

    function add()
    {
        if (empty($this->name))
            throw new EmptyParameterException('name');

        // TODO: validate the email address
        if (($this->basetype == ROLES_USERTYPE) && (empty($this->uname) || empty($this->email)))
            throw new EmptyParameterException('user name and valid email address.');

        // Confirm that this group or user does not already exist
        $q = new xarQuery('SELECT',$this->rolestable);
        if ($this->basetype == ROLES_GROUPTYPE) {
            $q->eq('xar_name',$this->name);
        } else {
            $q->eq('xar_uname',$this->uname);
        }

        if (!$q->run()) return;

        if ($q->getrows() > 0) {
            throw new DuplicateException(array('role',($this->type==1)?$this->name:$this->uname));
            }

        $nextId = $this->dbconn->genID($this->rolestable);

        $q = new xarQuery('INSERT',$this->rolestable);
        $tablefields = array(
            array('name' => 'xar_uid',        'value' => $nextId),
            array('name' => 'xar_name',       'value' => $this->name),
            array('name' => 'xar_uname',      'value' => $this->uname),
            array('name' => 'xar_date_reg',   'value' => time()),
            array('name' => 'xar_valcode',    'value' => $this->val_code),
            array('name' => 'xar_auth_modid', 'value' => $this->auth_module),
            array('name' => 'xar_type',       'value' => $this->type),
        );
        $q->addfields($tablefields);
        if ($this->basetype == ROLES_USERTYPE) {
            $userfields = array(
                array('name' => 'xar_email',      'value' => $this->email),
                array('name' => 'xar_pass',       'value' => md5($this->pass)),
                array('name' => 'xar_state',      'value' => $this->state),
                array('name' => 'xar_auth_modid','value' => $this->auth_module)
            );
            $q->addfields($userfields);
        }
        // Execute the query, bail if an exception was thrown
        if (!$q->run()) return;

        // Fetch the last inserted user ID, bail if an exception was thrown
        $this->uid = $this->dbconn->PO_Insert_ID($this->rolestable, 'xar_uid');
        if (!$this->uid) return;

        foreach ($this->duvs as $key => $value) xarModSetUserVar('roles',$key,$value,$this->uid);

        $parts = new xarRoles();
        $parentpart = $parts->getRole($this->parentid);
        return $parentpart->addMember($this);
    }

    /**
     * addMember: adds a role to a group
     *
     * Make a user or group a member of another group.
     * A user of group can have any number of parents or children..
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param role $ object
     * @return boolean
     * @throws none
     * @todo none
     */
    function addMember($member)
    {
        // bail if the purported parent is not a group.
        if ($this->isUser()) return false;

        $q = new xarQuery('SELECT',$this->rolememberstable);
        $q->eq('xar_uid',$member->getID());
        $q->eq('xar_parentid',$this->getID());
        if (!$q->run()) return;
        // This relationship already exists. Move on
        if ($q->row() != array()) return true;

        // add the necessary entry to the rolemembers table
        $q = new xarQuery('INSERT',$this->rolememberstable);
        $q->addfield('xar_uid',$member->getID());
        $q->addfield('xar_parentid',$this->getID());
        if (!$q->run()) return;

        // for children that are users
        // add 1 to the users field of the parent group. This is for display purposes.
        if ($member->isUser()) {
            // get the current count
            $query = "SELECT xar_users FROM $this->rolestable WHERE xar_uid = ?";
            $result = $this->dbconn->Execute($query,array($this->getID()));

            // add 1 and update.
            list($users) = $result->fields;
            $users = $users + 1;
            $query = "UPDATE " . $this->rolestable . " SET xar_users = ? WHERE xar_uid = ?";
            $bindvars = array($users,$this->getID());
            $this->dbconn->Execute($query,$bindvars);
        }
        // empty the privset cache
        // $privileges = new xarPrivileges();
        // $privileges->forgetprivsets();
        // done
        return true;
    }

    /**
     * removeMember: removes a role from a group
     *
     * Removes a user or group as an entry of another group.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param role $ object
     * @return boolean
     * @throws none
     * @todo add transaction around the delete and the update
     */
    function removeMember($member)
    {
        // delete the relevant entry from the rolemembers table
        $query = "DELETE FROM $this->rolememberstable WHERE xar_uid= ? AND xar_parentid= ?";
        $bindvars = array($member->getID(), $this->getID());
        $this->dbconn->Execute($query,$bindvars);
        // for children that are users
        // subtract 1 from the users field of the parent group. This is for display purposes.
        if ($member->isUser()) {
            // get the current count.
            $query = "SELECT xar_users FROM $this->rolestable WHERE xar_uid = ?";
            $result = $this->dbconn->Execute($query,array($this->getID()));

            // subtract 1 and update.
            list($users) = $result->fields;
            $users = $users - 1;
            $query = "UPDATE " . $this->rolestable . " SET xar_users = ? WHERE xar_uid = ?";
            $bindvars = array($users, $this->getID());
            $this->dbconn->Execute($query,$bindvars);
        }
        // empty the privset cache
        // $privileges = new xarPrivileges();
        // $privileges->forgetprivsets();
        // done
        return true;
    }

    function update()
    {
        $q = new xarQuery('UPDATE',$this->rolestable);
        $q->addfield('xar_name',$this->name);
        $q->addfield('xar_type',$this->type);
        $q->addfield('xar_uname',$this->uname);
        $q->addfield('xar_email',$this->email);
        $q->addfield('xar_state',$this->state);
        $q->addfield('xar_auth_modid',$this->auth_module);
        if ($this->pass != '') $q->addfield('xar_pass',md5($this->pass));
        $q->eq('xar_uid',$this->getID());

        // Execute the query, bail if an exception was thrown
        if (!$q->run()) return;

        foreach ($this->duvs as $key => $value) xarModSetUserVar('roles',$key,$value,$this->getID());
        return true;
    }

    /**
     * remove: make a role deleted
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return boolean
     * @throws none
     * @todo flag illegal deletes
     */
    function remove()
    {
        // get a list of all relevant entries in the rolemembers table
        // where this role is the child
        $query = "SELECT xar_parentid FROM $this->rolememberstable WHERE xar_uid= ?";
        // Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($this->getID()));

        // get the Roles class so we can use its methods
        $parts = new xarRoles();
        // go through the list, retrieving the roles and detaching each one
        // we need to do it this way because the method removeMember is more than just
        // a simple SQL DELETE
        while (!$result->EOF) {
            list($parentid) = $result->fields;
            $parentpart = $parts->getRole($parentid);
            // Check that a parent was returned
            if ($parentpart) {
                $parentpart->removeMember($this);
            }
            $result->MoveNext();
        }
        // delete the relevant entry in the roles table
        //$query = "DELETE FROM $this->rolestable
        //      WHERE xar_uid=" . $this->getID();

        //Let's not remove the role yet.  Instead, we want to deactivate it

        $deleted = xarML('deleted');
        $q = new xarQuery('UPDATE',$this->rolestable);
        $q->addfield('xar_uname',$this->getUser() . "[" . $deleted . "]" . time());
        $q->addfield('xar_email',$this->getEmail() . "[" . $deleted . "]" . time());
        $q->addfield('xar_state',ROLES_STATE_DELETED);
        $q->eq('xar_uid',$this->getID());

        // Execute the query, bail if an exception was thrown
        if (!$q->run()) return;
        // done

// get all the privileges that were assigned to this role
        $privileges = $this->getAssignedPrivileges();
// remove the privilege assignments for this role
        foreach ($privileges as $priv) {
            $this->removePrivilege($priv);
        }

// CHECKME: re-assign all privileges to the child roles ? (probably not)

        return true;
    }


    /**
     * purge: make a role purged
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return boolean
     * @throws none
     */
    function purge()
    {
        // no checks here. just do it
        $this->remove();
        $state = ROLES_STATE_DELETED;
        $uname = xarML('deleted') . microtime(TRUE) .'.'. $this->uid;
        $name = '';
        $pass = '';
        $email = '';
        $date_reg = '';
        $q = new xarQuery('UPDATE',$this->rolestable);
        $q->addfield('xar_name',$name);
        $q->addfield('xar_uname',$uname);
        $q->addfield('xar_pass',$pass);
        $q->addfield('xar_email',$email);
        $q->addfield('xar_date_reg',$date_reg);
        $q->addfield('xar_state',$state);
        $q->eq('xar_uid',$this->uid);
        if(!$q->run()) return;
        return true;
    }


    /**
     * Gets all the privileges in the database.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return array of privilege arrays like ('pid' => x, 'name' => y)
     */
    function getAllPrivileges()
    {
        static $allprivileges = array();
        if (empty($allprivileges)) {
            $query = "SELECT xar_pid, xar_name FROM $this->privilegestable ORDER BY xar_name";
            $result = $this->dbconn->Execute($query);

            $ind = 0;
            while (!$result->EOF) {
                list($pid, $name) = $result->fields;
                $allprivileges[$ind++] = array('pid' => $pid, 'name' => $name);
                $result->MoveNext();
            }
        }
        return $allprivileges;
    }


    /**
     * Gets all the privileges assigned directly to this role.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return array of privilege objects
     * @todo seems to me this belongs in privileges.
     */
    function getAssignedPrivileges()
    {
        static $stmt = null;  // For each uid, the query is the same, prepare it once.

        $cacheKey = "Privileges.ByUid";
        if(xarVarIsCached($cacheKey,$this->uid)) {
            return xarVarGetCached($cacheKey,$this->uid);
        }
        // We'll have to get it.
        xarLogMessage("ROLE: getting privileges for uid: $this->uid");
        $query = "SELECT  xar_pid, xar_name, xar_realm, xar_module,
                          xar_component, xar_instance, xar_level, xar_description
                  FROM    $this->privilegestable p, $this->acltable acl
                  WHERE   p.xar_pid = acl.xar_permid AND acl.xar_partid = ?";
        if(!isset($stmt)) $stmt = $this->dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($this->uid));

        sys::import('modules.privileges.xarprivileges');
        $privileges = array();
        while ($result->next()) {
            list($pid, $name, $realm, $module, $component, $instance, $level,
                $description) = $result->fields;
            $perm = new xarPrivilege(array('pid' => $pid,
                    'name' => $name,
                    'realm' => $realm,
                    'module' => $module,
                    'component' => $component,
                    'instance' => $instance,
                    'level' => $level,
                    'description' => $description,
                    'parentid' => 0));
            array_push($privileges, $perm);
        }
        xarVarSetCached($cacheKey,$this->uid,$privileges);
        return $privileges;
    }


    /**
     * Gets all the privileges inherited by this role.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return array of privilege objects
     */
    function getInheritedPrivileges()
    {
        // mrb: is this only dependent on $this->uid? if so, we can cache it too.
        $ancestors = $this->getAncestors();
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
    function hasPrivilege($privname)
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
     * @access public
     * @param privilege $ object
     * @return boolean
     * @throws none
     * @todo none
     */
    function assignPrivilege($perm)
    {
        // create an entry in the privmembers table
        $query = "INSERT INTO $this->acltable VALUES (?,?)";
        $bindvars = array($this->getID(),$perm->getID());
        $this->dbconn->Execute($query,$bindvars);
        // empty the privset cache
        // $privileges = new xarPrivileges();
        // $privileges->forgetprivsets();
        return true;
    }

    /**
     * removePrivilege: removes a privilege from a role
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param privilege $ object
     * @return boolean
     * @throws none
     * @todo none
     */
    function removePrivilege($perm)
    {
        // remove an entry from the privmembers table
        $query = "DELETE FROM $this->acltable
                  WHERE xar_partid= ? AND xar_permid= ?";
        $bindvars = array($this->uid, $perm->getID());
        $this->dbconn->Execute($query,$bindvars);
        // empty the privset cache
        // $privileges = new xarPrivileges();
        // $privileges->forgetprivsets();
        return true;
    }

    /**
     * getUsers: get the members of a group that are users
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param integer state get users in this state
     * @param integer startnum get users beyond this number
     * @param integer numitems get a defined number of users
     * @param string order order the result (name, uname, type, email, date_reg, state...)
     * @param string selection get users within this selection criteria
     * @return boolean
     * @throws none
     * @todo none
     */
    function getUsers($state = ROLES_STATE_CURRENT, $startnum = 0, $numitems = 0, $order = 'name', $selection = NULL)
    {
        $query = "SELECT r.xar_uid, r.xar_name, r.xar_type, r.xar_uname,
                         r.xar_email, r.xar_pass, r.xar_date_reg,
                         r.xar_valcode, r.xar_state,r.xar_auth_modid
                  FROM $this->rolestable r, $this->rolememberstable rm
                  WHERE r.xar_uid = rm.xar_uid AND
                        r.xar_type = ? AND
                        r.xar_state != ? AND
                        rm.xar_parentid = ?";
        // set up the query and get the data
        if ($state == ROLES_STATE_CURRENT) {
             $bindvars = array(ROLES_USERTYPE,ROLES_STATE_DELETED,$this->uid);

        } else {
             $bindvars = array(ROLES_USERTYPE, $state, $this->uid);
        }
        if (isset($selection)) $query .= $selection;
        $query .= " ORDER BY xar_" . $order;
        if ($startnum != 0) {
            $result = $this->dbconn->SelectLimit($query, $numitems, $startnum-1,$bindvars);
        } else {
            $result = $this->dbconn->Execute($query,$bindvars);
        }

        // CHECKME: I suppose this is what you meant here ?
        $parentid = $this->uid;
        // arrange the data in an array of role objects
        $users = array();
        while (!$result->EOF) {
            list($uid, $name, $type, $uname, $email, $pass,
                $date_reg, $val_code, $state, $auth_module) = $result->fields;
            $pargs = array('uid' => $uid,
                           'name' => $name,
                           'type' => $type,
                           'parentid' => $parentid,
                           'uname' => $uname,
                           'email' => $email,
                           'pass' => $pass,
                           'date_reg' => $date_reg,
                           'val_code' => $val_code,
                           'state' => $state,
                           'auth_module' => $auth_module);
            $duvarray = array('userhome','primaryparent','passwordupdate','timezone');
            $vars = array();
            foreach ($duvarray as $key) {
                if (xarModGetVar('roles',$key)) {
                    $vars[$key] = xarModGetUserVar('roles',$key,$pargs['uid']);
                    $vars[$key] = ($vars[$key] == 1) ? '' : $vars[$key];
                }
            }
            $pargs = array_merge($pargs,$vars);
            $users[] = new xarRole($pargs);
            $result->MoveNext();
        }
        // done
        return $users;
    }

    /**
     * countChildren: count the members of a group
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param integer state count user in this state
     * @param string selection count user within this selection criteria
     * @param integer type group or user
     * @return boolean
     * @throws none
     * @todo none
     */
    function countChildren($state = ROLES_STATE_CURRENT, $selection = NULL, $type = NULL)
    {
        $q = new xarQuery('SELECT');
        $q->addfield('COUNT(r.xar_uid) AS children');
        $q->addtable($this->rolestable,'r');
        $q->addtable($this->rolememberstable,'rm');
        $q->join('r.xar_uid', 'rm.xar_uid');
        $q->eq('rm.xar_parentid', $this->uid);
        if ($state == ROLES_STATE_CURRENT) {
            $q->ne('r.xar_state', ROLES_STATE_DELETED);
        } else {
            $q->eq('r.xar_state', $state);
        }
        if (isset($type)) $q->eq('r.xar_type', $type);

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
     * @access public
     * @param integer state count user in this state
     * @param string selection count user within this selection criteria
     * @return boolean
     * @throws none
     * @todo none
     */
    function countUsers($state = ROLES_STATE_CURRENT, $selection = NULL)
    {
        return $this->countChildren(0, $state, $selection);
    }

    /**
     * getParents: returns the parent objects of a role
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return array of role objects
     * @throws none
     * @todo none
     */
    function getParents()
    {
        static $stmt = null;  // The query below is the same for each uid, prepare it once.

        $cacheKey = 'RoleParents.ByUid';
        // create an array to hold the objects to be returned
        $parents = array();
        // if this is the root return an empty array
        if ($this->getID() == 1) return $parents;

        // if it's cached, we can return it
        if(xarVarIsCached($cacheKey,$this->uid)) {
            return xarVarGetCached($cacheKey,$this->uid);
        }

        // if this is a user just perform a SELECT on the rolemembers table
        $query = "SELECT r.*
                  FROM $this->rolestable r, $this->rolememberstable rm
                  WHERE r.xar_uid = rm.xar_parentid AND rm.xar_uid = ?";
        if(!isset($stmt)) $stmt = $this->dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($this->uid));

        // collect the table values and use them to create new role objects
        while ($result->next()) {
            list($uid, $name, $type, $parentid, $uname, $email, $pass,
                $date_reg, $val_code, $state, $auth_module) = $result->fields;
            $pargs = array('uid' => $uid,
                           'name' => $name,
                           'type' => $type,
                           'parentid' => $parentid,
                           'uname' => $uname,
                           'email' => $email,
                           'pass' => $pass,
                           'date_reg' => $date_reg,
                           'val_code' => $val_code,
                           'state' => $state,
                           'auth_module' => $auth_module);
            $duvarray = array('userhome','primaryparent','passwordupdate','timezone');
            $vars = array();
            foreach ($duvarray as $key) {
                if (xarModGetVar('roles',$key)) {
                    $vars[$key] = xarModGetUserVar('roles',$key,$pargs['uid']);
                    $vars[$key] = ($vars[$key] == 1) ? '' : $vars[$key];
                }
            }
            $pargs = array_merge($pargs,$vars);
            $parents[] = new xarRole($pargs);
        }
        // done
        xarVarSetCached($cacheKey,$this->uid,$parents);
        return $parents;
    }

    /**
     * getAncestors: returns all objects in the roles hierarchy above a role
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none
     * @return array of role objects. The objects can be queried with the getLevel() method to show their relationship (1=prents, 2=grandparents etc.).
     * @throws none
     */
    function getAncestors()
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
     * @access public
     * @param integer state get users in this state
     * @return list of users
     * @throws none
     * @todo evaluate performance of this (3 loops, of which 2 nested)
     */
    function getDescendants($state = ROLES_STATE_CURRENT, $grpflag=0)
    {
        $roles = new xarRoles();
        $role = $roles->getRole($this->uid);
        $users = $role->getUsers($state);
        $groups = $roles->getSubGroups($this->uid);
        $ua = array();
        foreach($users as $user){
            //using the ID as the key so that if a person is in more than one sub group they only get one email (mrb: email?)
            $ua[$user->getID()] = $user;
        }
        //Get the sub groups and go for another round
        foreach($groups as $group){
            $role = $roles->getRole($group['uid']);
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
     * @access public
     * @param none $
     * @return boolean
     * @throws none
     * @todo none
     */
    function isEqual($role)
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
     * @access public
     * @param none $
     * @return boolean
     * @throws none
     * @todo none
     */
    function isUser()
    {
        $base = xarModAPIFunc('dynamicdata','user','getbaseancestor',array('itemtype' => $this->getType(), 'moduleid' => 27));
        return $base['itemtype'] == ROLES_USERTYPE;
    }

    /**
     * isParent: checks whether a role is a parent of this one
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return boolean
     * @throws none
     * @todo none
     */
    function isParent($role)
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
     * @access public
     * @param none $
     * @return boolean
     * @throws none
     * @todo none
     */
    function isAncestor($role)
    {
        $ancestors = $this->getAncestors();
        foreach ($ancestors as $ancestor) {
            if ($role->isEqual($ancestor)) return true;
        }
        return false;
    }

    /**
     * adjustParentUsers: adjust of a user's parent user tallies
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param integer
     * @return boolean
     * @throws none
     * @todo none
     */
    function adjustParentUsers($adjust)
    {
        $q = new xarQuery('SELECT', $this->rolestable, 'xar_users AS users');
        $q1 = new xarQuery('UPDATE', $this->rolestable);
        $parents = $this->getParents();
        foreach ($parents as $parent) {
            $q->clearconditions();
            $q->eq('xar_uid', $parent->getID());
            $q1->clearconditions();
            $q1->eq('xar_uid', $parent->getID());

            // get the current count.
            if (!$q->run()) return;
            $row = $q->row();

            // adjust and update update.
            $q1->addfield('xar_users', $row['users'] + $adjust);
            if (!$q1->run()) return;
        }
        return true;
    }

    /**
     * getPrivileges: returns the privileges in the privileges repository
     *
     * Returns an array of all the privileges objects
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return array of privilege objects
     * @throws none
     * @todo is this still used?
     */
    function getPrivileges()
    {
        /*  // start by getting an array of all the privileges
            $query = "SELECT * FROM $this->privilegestable";
            $result = $this->dbconn->Execute($query);


            $privileges = array();
            while(!$result->EOF) {
                list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
                $pargs = array('pid' => $pid,
                            'name' => $name,
                            'realm'=>$realm,
                            'module'=>$module,
                            'component'=>$component,
                            'instance'=>$instance,
                            'level'=>$level,
                            'description'=>$description);
                array_push($privileges,new xarPrivilege($pargs))
                $result->MoveNext();
            }

    // start by getting an array of the parents
            $parents = $part->getParents();

    //Get the parent field for each parent
            while (list($key, $parent) = each($parents)) {
                $ancestors = $parent->getParents();
                foreach ($ancestors as $ancestor) {

    //If this is a new ancestor add to the end of the array
                    $iscontained = false;
                    foreach ($parents as $parent){
                        if ($parent->isEqual($ancestor)) $iscontained = true;
                    }
                    if (!$iscontained) array_push($parents, $ancestor);
                }
            }
*/ }

    /**
     * Gets and Sets
     *
     * Get and set methods for the class variables
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param n $ /a
     * @return n /a
     * @throws none
     * @todo since there are so many a generalized getter (magic __get() ) might be more pleasurable
     */
    function getID()
    {
        return $this->uid;
    }
    function getName()
    {
        return $this->name;
    }
    function getHome()
    {
        $duv = isset($this->duvs['userhome']) ? $this->duvs['userhome'] : "";
        return $duv;
    }
    function getPrimaryParent()
    {
        $duv = isset($this->duvs['primaryparent']) ? $this->duvs['primaryparent'] : "";
        return $duv;
    }
    function getUname()
    {
        return $this->uname;
    }
    function getType()
    {
        return $this->type;
    }
    function getUser()
    {
        return $this->uname;
    }
    function getEmail()
    {
        return $this->email;
    }
    function getPass()
    {
        return $this->pass;
    }
    function getState()
    {
        return $this->state;
    }
    function getDateReg()
    {
        return $this->date_reg;
    }
    function getValCode()
    {
        return $this->val_code;
    }
    function getAuthModule()
    {
        return $this->auth_module;
    }
    function getLevel()
    {
        return $this->parentlevel;
    }

    function setName($var)
    {
        $this->name = $var;
    }
    function setHome($var)
    {
        $this->userhome = $var;
    }
    function setPrimaryParent($var)
    {
        $this->primaryparent = $var;
    }
    function setUname($var)
    {
        $this->uname = $var;
    }
    function setParent($var)
    {
        $this->parentid = $var;
    }
    function setUser($var)
    {
        $this->uname = $var;
    }
    function setEmail($var)
    {
        $this->email = $var;
    }
    function setPass($var)
    {
        $this->pass = $var;
    }
    function setState($var)
    {
        $this->state = $var;
    }
    function setDateReg($var)
    {
        $this->date_reg = $var;
    }
    function setValCode($var)
    {
        $this->val_code = $var;
    }
    function setAuthModule($var)
    {
        $this->auth_module = $var;
    }
    function setLevel($var)
    {
        $this->parentlevel = $var;
    }
}

?>
