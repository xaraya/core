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
sys::import('modules.roles.class.xarQuery');

class xarRoles extends Object
{
    const ROLES_STATE_DELETED = 0;
    const ROLES_STATE_INACTIVE = 1;
    const ROLES_STATE_NOTVALIDATED = 2;
    const ROLES_STATE_ACTIVE = 3;
    const ROLES_STATE_PENDING = 4;
    const ROLES_STATE_CURRENT = 98;
    const ROLES_STATE_ALL = 99;

    const ROLES_ROLETYPE = 1;
    const ROLES_USERTYPE = 2;
    const ROLES_GROUPTYPE = 3;

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

        $duvarray = array('userhome','primaryparent','passwordupdate','userlastlogin','usertimezone');
        $duvs = array();
        foreach ($duvarray as $key) {
            $duv = xarModGetUserVar('roles',$key,$row['xar_uid']);
            if (!empty($duv)) $duvs[$key] = $duv;
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
        sys::import('modules.roles.class.role');
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
        sys::import('modules.roles.class.role');
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
    function makeUser($name, $uname, $email, $pass = 'xaraya', $datereg = '', $valcode = '', $state = ROLES_STATE_ACTIVE, $authmodule = 0, $duvs=array())
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
            array('name' => 'xar_auth_modid',  'value' => $authmodule),
        );
        $q = new xarQuery('INSERT',$this->rolestable);
        $q->addfields($tablefields);
        if (!$q->run()) return;
        foreach($duvs as $key => $value) xarModSetUserVar($key, $value, $nextId);
        // set email option to false
        xarModSetUserVar('roles','usersendemails', false, $nextId);
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

?>
