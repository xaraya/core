<?php
/**
 * xarRoles class
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */

sys::import('modules.roles.class.xarQuery');
/**
 * xarRoles: class for the role repository
 *
 * Represents the repository containing all roles
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 */
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

    protected static $dbconn;
    protected static $rolestable;
    protected static $rolememberstable;

    public $allgroups = array();
    public $users = array();

    public static function initialize()
    {
        self::$dbconn =& xarDBGetConn();
        xarModAPILoad('roles');
        $xartable =& xarDBGetTables();
        self::$rolestable = $xartable['roles'];
        self::$rolememberstable = $xartable['rolemembers'];
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
     * @return array of arrays representing all the groups
     */
    public static function getgroups()
    {
        static $allgroups = array();
        if (empty($allgroups)) {
            $q = self::_getgroupsquery();
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
     * @param integer $uid
     * @return array representing the group
     */
    public static function getgroup($uid)
    {
        $q = self::_getgroupsquery();
        $q->eq('r.xar_uid',$uid);
        if (!$q->run()) return;
        if ($q->row() != array()) return $q->row();
        return false;
    }

    /**
     * getsubgroups: get the children of a group that are groups themselves
     *
     * This function is useful for setting up trees
     * We don't include users in the tree because there are too many to display
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param int $uid
     * @return array representing the subgroups of a group
     */
    public static function getsubgroups($uid)
    {
        $subgroups = array();
        $groups = self::getgroups();
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
     * @param integer $uid
     * @return object role
     */
    public static function getRole($uid)
    {
        $cacheKey = 'Roles.ByUid';
        if(xarVarIsCached($cacheKey,$uid)) {
            return xarVarGetCached($cacheKey,$uid);
        }
        // Need to get it from DB.
        // TODO: move caching to _lookuprole?
        $r = self::_lookuprole('xar_uid',(int) $uid);
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
     * @param string $name
     * @return object role
     * @todo cache this too?
     */
    public static function findRole($name)
    {
        return self::_lookuprole('xar_name',$name,$state=ROLES_STATE_ACTIVE);
    }

    /**
     * ufindRole: finds a single role based on its username
     *
     * @param string $uname
     * @return object role
     * @todo cache this too?
     */
    public static function ufindRole($uname)
    {
        return self::_lookuprole('xar_uname',$uname,$state=ROLES_STATE_ACTIVE);
    }

    /**
     * makeMemberByName: makes a role a child of a group
     *
     * Creates an entry in the rolemembers table
     * This is a convenience class for module developers
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param string $childname
     * @param string $parentname
     * @return bool
     * @todo create exceptions for bad input
     * @todo seems we could do this in one query instead of two?
     */
    public static function makeMemberByName($childname, $parentname)
    {
        self::initialize();
        // retrieve the parent's data from the repository
        $query = "SELECT * FROM " . self::$rolestable . " WHERE xar_name = ?";
        // Prepare it once
        $stmt = self::$dbconn->prepareStatement($query);

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
     * This is a convenience class for module developers
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param string $rootname
     * @return bool
     * @todo create exceptions for bad input
     */
    public static function isRoot($rootname)
    {
        self::initialize();
        // get the data for the root object
        $query = "SELECT xar_uid
                  FROM " . self::$rolestable .
                  " WHERE xar_name = ?";
        // Execute the query, bail if an exception was thrown
        $result = self::$dbconn->Execute($query,array($rootname));

        // create the entry
        list($uid) = $result->fields;
        $query = "INSERT INTO " . self::$rolememberstable .
                " VALUES (?,?)";
        // Execute the query, bail if an exception was thrown
        self::$dbconn->Execute($query, array($uid,null));
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
     * @param string $name
     * @param string $uname
     * @param string $email
     * @param string $pass
     * @param string $datereg
     * @param string $valcode
     * @param int    $state
     * @param int    $authmodule
     * @param array  $duvs
     * @return bool
     * @todo create exception handling for bad input
     */
    public static function makeUser($name, $uname, $email, $pass = 'xaraya', $datereg = '', $valcode = '', $state = ROLES_STATE_ACTIVE, $authmodule = 0, $duvs=array())
    {
        // TODO: validate the email address
        if (empty($name) && empty($uname) || empty($email)) {
            $msg = 'You must enter a user name and a valid email address.';
            throw new EmptyParameterException(null,$msg);
        }

        // set up the query and create the entry
        $tablefields = array(
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
        self::initialize();
        $q = new xarQuery('INSERT',self::$rolestable);
        $q->addfields($tablefields);
        if (!$q->run()) return;
        $nextId = self::$dbconn->getLastId(self::$rolestable);
        foreach($duvs as $key => $value) xarModSetUserVar($key, $value, $nextId);
        // set email option to false
        // FIXME: this fails during installation, as the modvar isnt known yet.
        // xarModSetUserVar('roles','usersendemails', false, $nextId);
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
     * @param string $name
     * @param string $uname
     * @return bool
     */
    public static function makeGroup($name,$uname='')
    {
        self::initialize();
        if ($uname == '') $uname = $name;

        // Confirm that this group or user does not already exist
        $q = new xarQuery('SELECT',self::$rolestable,'COUNT(*) AS groupcount');
        $q->eq('xar_name',$name);
        $q->ne('xar_state',ROLES_STATE_DELETED);
        if (!$q->run()) return;

        $row = $q->row();
        if ($row['groupcount'] > 0) {
            throw new DuplicateException(array('group',$name));
            return false;
        }

        $createdate = time();
        $query = "INSERT INTO " . self::$rolestable .
                   " (xar_name, xar_type, xar_uname,xar_date_reg)
                  VALUES (?,?,?,?)";
        $bindvars = array($name, ROLES_GROUPTYPE, $uname, $createdate);
        self::$dbconn->Execute($query,$bindvars);
        // done
        return true;
    }

    /**
     * _getgroupsquery: query for getting groups
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     */
    private static function _getgroupsquery()
    {
        $types = xarModAPIFunc('dynamicdata','user','getmoduleitemtypes',array('moduleid' => 27));
        $basetypes = array();
        foreach ($types as $key => $value) {
            $basetype = xarModAPIFunc('dynamicdata','user','getbaseancestor',array('itemtype' => $key, 'moduleid' => 27));
            if ($basetype['itemtype'] == ROLES_GROUPTYPE) $basetypes[] = $key;
        }
        // set up the query and get the groups
        self::initialize();
        $q = new xarQuery('SELECT');
        $q->addtable(self::$rolestable,'r');
        $q->addtable(self::$rolememberstable,'rm');
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
     * _lookuprole : Lookup a row based upon a specified field
     *
     * @param string $field
     * @param mixed  $value
     * @param int    $state
     * @return object a role
     */
    private static function _lookuprole($field,$value,$state=ROLES_STATE_ALL)
    {
        // retrieve the object's data from the repository
        // set up and execute the query
        self::initialize();
        $q = new xarQuery('SELECT',self::$rolestable);
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
}
?>
