<?php
/**
 * File: $Id$
 *
 * Purpose of file:  Roles administration API
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

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
class xarRoles {
    var $allgroups = array();
    var $users = array();
    var $dbconn;
    var $rolestable;
    var $rolememberstable;

    function xarRoles()
    {
        list($this->dbconn) = xarDBGetConn();
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
        // check if we already have the groups stored
        if ((!isset($this->allgroups)) || count($this->allgroups) == 0) {
            // set up the query and get the groups
            $query = "SELECT r.xar_uid,
                        r.xar_name,
                        r.xar_users,
                        rm.xar_parentid
                        FROM $this->rolestable AS r, $this->rolememberstable AS rm
                        WHERE r.xar_uid = rm.xar_uid
                        AND r.xar_type = 1
                        ORDER BY r.xar_name";

            $result = $this->dbconn->Execute($query);
            if (!$result) return;
            // arrange the data in an array
            $groups = array();
            while (!$result->EOF) {
                list($uid, $name, $users, $parentid) = $result->fields;
                $groups[] = array('uid' => $uid,
                    'name' => $name,
                    'users' => $users,
                    'parentid' => $parentid);
                $result->MoveNext();
            }
            $this->allgroups = $groups;
            return $groups;
        } else {
            return $this->allgroups;
        }
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
        foreach($this->getgroups() as $group) {
            if ($group['uid'] == $uid) return $group;
        }
        return false;
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
        // retrieve the object's data from the repository
        // set up and execute the query
        $query = "SELECT *
                  FROM $this->rolestable
                  WHERE xar_uid = $uid";
        // Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;
        // set the data in an array
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
        // create and return the role object
        return new xarRole($pargs);
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
     * @todo none
     */
    function findRole($name)
    {
        // retrieve the object's data from the repository
        // set up and execute the query
        $query = "SELECT *
                  FROM $this->rolestable
                  WHERE xar_name = '$name'";
        // Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;

        if (!$result->EOF) {
            // set the data in an array
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
            // create and return the role object
            return new xarRole($pargs);
        }
    }

    function ufindRole($name)
    {
        // retrieve the object's data from the repository
        // set up and execute the query
        $query = "SELECT *
                  FROM $this->rolestable
                  WHERE xar_uname = '$name'";
        // Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;

        if (!$result->EOF) {
            // set the data in an array
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
            // create and return the role object
            return new xarRole($pargs);
        }
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
     */
    function makeMemberByName($childname, $parentname)
    {
        // retrieve the parent's data from the repository
        $query = "SELECT *
                  FROM $this->rolestable
                  WHERE xar_name = '$parentname'";
        // Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;
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
        $query = "SELECT *
                  FROM $this->rolestable
                  WHERE xar_name = '$childname'";
        // Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;
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
                  WHERE xar_name = '$rootname'";
        // Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;
        // create the entry
        list($uid) = $result->fields;
        $query = "INSERT INTO $this->rolememberstable
                VALUES ($uid,0)";
        // Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query)) return;
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
    function makeUser($name, $uname, $email, $pass = 'xaraya', $datereg = '', $valcode = '', $state = 3, $authmodule = '')
    {
        // TODO: validate the email address
        if (empty($name) && empty($uname) || empty($email)) {
            $msg = xarML('You must enter a user name and a valid email address.',
                'roles');
            xarExceptionSet(XAR_USER_EXCEPTION,
                'DUPLICATE_DATA',
                new DefaultUserException($msg));
            xarSessionSetVar('errormsg', _MODARGSERROR);
            return false;
        }
        // Confirm that this group or user does not already exist
        $query = "SELECT COUNT(*) FROM $this->rolestable
                  WHERE xar_uname = '$uname'";

        $result = $this->dbconn->Execute($query);
        if (!$result) return;

        list($count) = $result->fields;

        if ($count == 1) {
            $msg = xarML('This entry already exists.',
                'roles');
            xarExceptionSet(XAR_USER_EXCEPTION,
                'DUPLICATE_DATA',
                new DefaultUserException($msg));
            xarSessionSetVar('errormsg', _GROUPALREADYEXISTS);
            return false;
        }
        // create an ID for the user
        $nextId = $this->dbconn->genID($this->rolestable);
        $createdate = mktime();
        // set up the query and create the entry
        $nextIdprep = xarVarPrepForStore($nextId);
        $nameprep = xarVarPrepForStore($name);
        $unameprep = xarVarPrepForStore($uname);
        $emailprep = xarVarPrepForStore($email);
        $passprep = xarVarPrepForStore(md5($pass));
        $dateregprep = xarVarPrepForStore($createdate);
        $valcodeprep = xarVarPrepForStore($valcode);
        $stateprep = xarVarPrepForStore($state);
        $authmoduleprep = xarVarPrepForStore($authmodule);
        $query = "INSERT INTO $this->rolestable
                    (xar_uid, xar_name, xar_type, xar_uname, xar_email, xar_pass,
                    xar_date_reg, xar_valcode, xar_state, xar_auth_module)
                  VALUES ($nextIdprep, '$nameprep', 0, '$unameprep', '$emailprep', '$passprep',
                  '$dateregprep', '$valcodeprep', $stateprep, '$authmoduleprep')";
        if (!$this->dbconn->Execute($query)) return;
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
     * @todo create exception handling for bad input
     */
    function makeGroup($name)
    {
        // Confirm that this group or user does not already exist
        $query = "SELECT COUNT(*) FROM $this->rolestable
                  WHERE xar_name = '$name'";

        $result = $this->dbconn->Execute($query);
        if (!$result) return;

        list($count) = $result->fields;
        if ($count == 1) {
            $msg = xarML('This entry already exists.',
                'roles');
            xarExceptionSet(XAR_USER_EXCEPTION,
                'DUPLICATE_DATA',
                new DefaultUserException($msg));
            xarSessionSetVar('errormsg', _GROUPALREADYEXISTS);
            return false;
        }
        // create an ID for the group
        $nextId = $this->dbconn->genID($this->rolestable);
        $createdate = mktime();
        // set up the query and create the entry
        $nextIdprep = xarVarPrepForStore($nextId);
        $nameprep = xarVarPrepForStore($name);
        $dateprep = xarVarPrepForStore($createdate);
        $query = "INSERT INTO $this->rolestable
                    (xar_uid, xar_name, xar_type, xar_uname,xar_date_reg)
                  VALUES ($nextIdprep, '$nameprep', 1, '$nameprep', '$dateprep')";
        if (!$this->dbconn->Execute($query)) return;
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
class xarRole {
    var $uid; //the id of this user or group
    var $name; //the name of this user or group
    var $type; //the type of this role (0=user, 1=group)
    var $parentid; //the id of the parent of this role
    var $uname; //the user name (not used by groups)
    var $email; //the email address (not used by groups)
    var $pass; //the password (not used by groups)
    var $date_reg; //the date of registration
    var $val_code; //the validation code of this user or group
    var $state; //the state of this user or group
    var $auth_module; //no idea what this is (not used by groups)
    var $parentlevel; //we use this just to store transient information

    var $dbconn;
    var $rolestable;
    var $rolememberstable;
    var $privilegestable;
    var $acltable;

    var $allprivileges;

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
    function xarRole($pargs)
    {
        extract($pargs);

        list($this->dbconn) = xarDBGetConn();
        $xartable =& xarDBGetTables();
        $this->rolestable = $xartable['roles'];
        $this->rolememberstable = $xartable['rolemembers'];
        $this->privilegestable = $xartable['privileges'];
        $this->acltable = $xartable['security_acl'];

        if (empty($uid)) $uid = 0;
        if (empty($parentid)) $parentid = 0;
        if (empty($uname)) $uname = xarSessionGetVar('uid') . time();
        if (empty($email)) $email = '';
        if (empty($pass)) $pass = '';
        if (empty($state)) $state = 1;
        // FIXME: why is date_reg a varchar in the database and not a date field?
        if (empty($date_reg)) $date_reg = mktime();
        if (empty($val_code)) $val_code = 'createdbyadmin';
        // FIXME: what is a sensible default for auth_module?
        if (empty($auth_module)) $auth_module = '';

        $this->uid = $uid;
        $this->name = $name;
        $this->type = $type;
        $this->parentid = $parentid;
        $this->uname = $uname;
        $this->email = $email;
        $this->pass = $pass;
        $this->state = $state;
        $this->date_reg = $date_reg;
        $this->val_code = $val_code;
        $this->auth_module = $auth_module;
        $this->parentlevel = 0;
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
        if (empty($this->name)) {
            $msg = xarML('You must enter a name.',
                'roles');
            xarExceptionSet(XAR_USER_EXCEPTION,
                'DUPLICATE_DATA',
                new DefaultUserException($msg));
            xarSessionSetVar('errormsg', _MODARGSERROR);
            return false;
        }
        // TODO: validate the email address
        if ((empty($this->type)) && (empty($this->uname) || empty($this->email))) {
            $msg = xarML('You must enter a user name and a valid email address.',
                'roles');
            xarExceptionSet(XAR_USER_EXCEPTION,
                'DUPLICATE_DATA',
                new DefaultUserException($msg));
            xarSessionSetVar('errormsg', _MODARGSERROR);
            return false;
        }
        // Confirm that this group or user does not already exist
        if ($this->type == 1) {
            $query = "SELECT COUNT(*) FROM $this->rolestable
                  WHERE xar_name = '$this->name'";
        } else {
            $query = "SELECT COUNT(*) FROM $this->rolestable
                  WHERE xar_uname = '$this->uname'";
        }

        $result = $this->dbconn->Execute($query);
        if (!$result) return;

        list($count) = $result->fields;

        if ($count == 1) {
            $msg = xarML('This entry already exists.',
                'roles');
            xarExceptionSet(XAR_USER_EXCEPTION,
                'DUPLICATE_DATA',
                new DefaultUserException($msg));
            xarSessionSetVar('errormsg', _GROUPALREADYEXISTS);
            return false;
        }

        $nextId = $this->dbconn->genID($this->rolestable);
        $createdate = mktime();

        if ($this->type == 1) {
            $nextIdprep = xarVarPrepForStore($nextId);
            $nameprep = xarVarPrepForStore($this->name);
            $typeprep = xarVarPrepForStore($this->type);
            $unameprep = xarVarPrepForStore($this->uname);
            $valcodeprep = xarVarPrepForStore($this->val_code);
            $dateregprep = xarVarPrepForStore($createdate);
            $query = "INSERT INTO $this->rolestable
                        (xar_uid, xar_name, xar_type, xar_uname, xar_valcode, xar_date_reg)
                      VALUES ($nextIdprep, '$nameprep', $typeprep, '$unameprep', '$valcodeprep', '$dateregprep')";
        } else {
            $nextIdprep = xarVarPrepForStore($nextId);
            $nameprep = xarVarPrepForStore($this->name);
            $typeprep = xarVarPrepForStore($this->type);
            $unameprep = xarVarPrepForStore($this->uname);
            $emailprep = xarVarPrepForStore($this->email);
            $passprep = xarVarPrepForStore(md5($this->pass));
            $dateregprep = xarVarPrepForStore($createdate);
            $stateprep = xarVarPrepForStore($this->state);
            $valcodeprep = xarVarPrepForStore($this->val_code);
            $authmodprep = xarVarPrepForStore($this->auth_module);
            $query = "INSERT INTO $this->rolestable
                        (xar_uid, xar_name, xar_type, xar_uname, xar_email, xar_pass,
                        xar_date_reg, xar_state, xar_valcode, xar_auth_module)
                      VALUES ($nextIdprep, '$nameprep', $typeprep, '$unameprep', '$emailprep',
                      '$passprep', '$dateregprep', $stateprep, '$valcodeprep', '$authmodprep')";
        }
        // Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query)) return;

        $query = "SELECT MAX(xar_uid) FROM $this->rolestable";
        // Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;

        list($uid) = $result->fields;
        $this->uid = $uid;
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
        // add the necessary entry to the rolemembers table
        $query = "INSERT INTO $this->rolememberstable
                VALUES (" . $member->getID() . "," . $this->getID() . ")";
        if (!$this->dbconn->Execute($query)) return;
        // for children that are users
        // add 1 to the users field of the parent group. This is for display purposes.
        if ($member->isUser()) {
            // get the current count
            $query = "SELECT xar_users FROM $this->rolestable
                    WHERE xar_uid =" . $this->getID();
            $result = $this->dbconn->Execute($query);
            if (!$result) return;
            // add 1 and update.
            list($users) = $result->fields;
            $users = $users + 1;
            $query = "UPDATE " . $this->rolestable . " SET " . "xar_users = $users" . " WHERE xar_uid =" . $this->getID();
            if (!$this->dbconn->Execute($query)) return;
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
     * @todo none
     */
    function removeMember($member)
    {
        // delete the relevant entry from the rolemembers table
        $query = "DELETE FROM $this->rolememberstable
              WHERE xar_uid=" . $member->getID() . " AND xar_parentid=" . $this->getID();
        if (!$this->dbconn->Execute($query)) return;
        // for children that are users
        // subtract 1 from the users field of the parent group. This is for display purposes.
        if ($member->isUser()) {
            // get the current count.
            $query = "SELECT xar_users FROM $this->rolestable
                    WHERE xar_uid =" . $this->getID();
            $result = $this->dbconn->Execute($query);
            if (!$result) return;
            // subtract 1 and update.
            list($users) = $result->fields;
            $users = $users - 1;
            $query = "UPDATE " . $this->rolestable . " SET " . "xar_users = $users" . " WHERE xar_uid =" . $this->getID();
            if (!$this->dbconn->Execute($query)) return;
        }
        // empty the privset cache
        // $privileges = new xarPrivileges();
        // $privileges->forgetprivsets();
        // done
        return true;
    }

    function update()
    {
        $query = "UPDATE " . $this->rolestable . " SET " . "xar_name = '$this->name'," . "xar_type = $this->type," . "xar_uname = '$this->uname'," . "xar_email = '$this->email'," . "xar_state = '$this->state'";
        if ($this->pass != '') $query .= ",xar_pass = '" . md5($this->pass) . "'";
        $query .= " WHERE xar_uid = " . $this->getID();
        // Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query)) return;
        return true;
    }

    /**
     * remove: remove a role from the repository
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
        $query = "SELECT xar_parentid FROM $this->rolememberstable
              WHERE xar_uid=" . $this->getID();
        // Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;
        // get the Roles class so we can use its methods
        $parts = new xarRoles();
        // go through the list, retrieving the roles and detaching each one
        // we need to do it this way because the method removeMember is more than just
        // a simple SQL DELETE
        while (!$result->EOF) {
            list($parentid) = $result->fields;
            $parentpart = $parts->getRole($parentid);
            $parentpart->removeMember($this);
            $result->MoveNext();
        }
        // delete the relevant entry in the roles table
        //$query = "DELETE FROM $this->rolestable
        //      WHERE xar_uid=" . $this->getID();

        //Let's not remove the role yet.  Instead, we want to deactivate it
        // and then purge it at a later time.

        $query = "UPDATE $this->rolestable
        SET xar_email   = '',
            xar_pass    = '',
            xar_valcode = '',
            xar_state   = '0'
        WHERE xar_uid   = " . $this->getID();

        // Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query)) return;
        // done
        return true;
    }

    function getAllPrivileges()
    {
        if ((!isset($allprivileges)) || count($allprivileges) == 0) {
            $query = "SELECT xar_pid,
                        xar_name
                        FROM $this->privilegestable
                        ORDER BY xar_name";

            $result = $this->dbconn->Execute($query);
            if (!$result) return;
            $privileges = array();
            $ind = 0;
            while (!$result->EOF) {
                list($pid, $name) = $result->fields;
                $ind = $ind + 1;
                $privileges[$ind] = array('pid' => $pid,
                    'name' => $name);
                $result->MoveNext();
            }
            $allprivileges = $privileges;
            return $privileges;
        } else {
            return $allprivileges;
        }
    }

    function getAssignedPrivileges()
    {
        $query = "SELECT xar_pid,
                    xar_name,
                    xar_realm,
                    xar_module,
                    xar_component,
                    xar_instance,
                    xar_level,
                    xar_description
                    FROM $this->privilegestable AS p, $this->acltable AS acl
                    WHERE p.xar_pid = acl.xar_permid
                      AND acl.xar_partid = $this->uid";
        // Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;

        include_once 'modules/privileges/xarprivileges.php';
        $privileges = array();
        while (!$result->EOF) {
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
            $result->MoveNext();
        }
        return $privileges;
    }

    function getInheritedPrivileges()
    {
        $ancestors = $this->getAncestors();
        $inherited = array();
        foreach ($ancestors as $ancestor) {
            $perms = $ancestor->getAssignedPrivileges();
            while (list($key, $perm) = each($perms)) {
                array_push($inherited, $perm);
            }
        }
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
        $query = "INSERT INTO $this->acltable
                VALUES (" . $this->getID() . "," . $perm->getID() . ")";
        if (!$this->dbconn->Execute($query)) return;
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
              WHERE xar_partid=" . $this->uid . " AND xar_permid=" . $perm->getID();
        if (!$this->dbconn->Execute($query)) return;
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
    function getUsers($state = 0, $startnum = 0, $numitems = 0, $order = 'name', $selection = NULL)
    {
        // set up the query and get the data
        if ($state == 0) {
            $query = "SELECT r.xar_uid,
                        r.xar_name,
                        r.xar_type,
                        r.xar_uname,
                        r.xar_email,
                        r.xar_pass,
                        r.xar_date_reg,
                        r.xar_valcode,
                        r.xar_state,
                        r.xar_auth_module
                        FROM $this->rolestable AS r, $this->rolememberstable AS rm
                        WHERE r.xar_uid = rm.xar_uid
                        AND r.xar_type = 0
                        AND rm.xar_parentid = $this->uid";
        } else {
            $query = "SELECT r.xar_uid,
                        r.xar_name,
                        r.xar_type,
                        r.xar_uname,
                        r.xar_email,
                        r.xar_pass,
                        r.xar_date_reg,
                        r.xar_valcode,
                        r.xar_state,
                        r.xar_auth_module
                        FROM $this->rolestable AS r, $this->rolememberstable AS rm
                        WHERE r.xar_uid = rm.xar_uid
                        AND r.xar_type = 0 AND r.xar_state = $state
                        AND rm.xar_parentid = $this->uid";
        }
        if (isset($selection)) $query .= $selection;
        $query .= " ORDER BY xar_" . $order;
        if ($startnum != 0) {
            $result = $this->dbconn->SelectLimit($query, $numitems, $startnum-1);
        } else {
            $result = $this->dbconn->Execute($query);
        }
        if (!$result) return;
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
            $users[] = new xarRole($pargs);
            $result->MoveNext();
        }
        // done
        return $users;
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
    function countUsers($state = 0, $selection = NULL)
    {
        // set up the query and get the data
        if ($state == 0) {
            $query = "SELECT COUNT(r.xar_uid)
                        FROM $this->rolestable AS r, $this->rolememberstable AS rm
                        WHERE r.xar_uid = rm.xar_uid
                        AND r.xar_type = 0
                        AND rm.xar_parentid = $this->uid";
        } else {
            $query = "SELECT COUNT(r.xar_uid)
                        FROM $this->rolestable AS r, $this->rolememberstable AS rm
                        WHERE r.xar_uid = rm.xar_uid
                        AND r.xar_type = 0 AND r.xar_state = $state
                        AND rm.xar_parentid = $this->uid";
        }
        if (isset($selection)) $query .= $selection;
        $result = $this->dbconn->Execute($query);
        if (!$result) return;
        list($numusers) = $result->fields;
        // done
        return $numusers;
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
        // create an array to hold the objects to be returned
        $parents = array();
        // if this is the root return an empty array
        if ($this->getID() == 1) return $parents;
        // if this is a group pick up the uids using getgroups()
        // May be faster
        if (!$this->isUser()) {
            // get the roles class
            $parts = new xarRoles();
            // look for the parent uids and create role objects from them
            foreach($parts->getgroups() as $group) {
                if ($group['uid'] == $this->uid) {
                    $parents[] = $parts->getRole($group['parentid']);
                }
            }
        } else {
            // if this is a user just perform a SELECT on the rolemembers table
            $query = "SELECT r.*
                        FROM $this->rolestable AS r, $this->rolememberstable AS rm
                        WHERE r.xar_uid = rm.xar_parentid
                        AND rm.xar_uid = $this->uid";
            $result = $this->dbconn->Execute($query);
            if (!$result) return;
            // collect the table values and use them to create new role objects
            while (!$result->EOF) {
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
                $parents[] = new xarRole($pargs);
                $result->MoveNext();
            }
        }
        // done
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
     * Users have type = 0.
     * Groups have type = 1.
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
        return $this->getType() == 0;
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
     * getPrivileges: returns the privileges in the privileges repository
     *
     * Returns an array of all the privileges objects
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return array of privilege objects
     * @throws none
     * @todo none
     */
    function getPrivileges()
    {
        /*  // start by getting an array of all the privileges
            $query = "SELECT * FROM $this->privilegestable";
            $result = $this->dbconn->Execute($query);
            if (!$result) return;

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
     * @todo none
     */
    function getID()
    {
        return $this->uid;
    }
    function getName()
    {
        return $this->name;
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
