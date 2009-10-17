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
        self::$dbconn = xarDB::getConn();
        xarMod::loadDbInfo('roles','roles');
        $xartable = xarDB::getTables();
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
        self::initialize();
        static $allgroups = array();
        if (empty($allgroups)) {
            $query = "SELECT r.id AS id, r.name AS name, r.users AS users, rm.parent_id AS parentid 
                      FROM " . self::$rolestable . " r LEFT JOIN " . self::$rolememberstable . " rm ON r.id = rm.role_id 
                      WHERE r.itemtype = ? AND r.state = ? ORDER BY r.name";
            $bindvars[] = ROLES_GROUPTYPE;
            $bindvars[] = ROLES_STATE_ACTIVE;
            $dbconn = xarDB::getConn();
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
            if(!$result) return;            
            while($result->next() > 0) $allgroups[] = $result->fields;
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
     * @param integer $id
     * @return array representing the group
     */
    public static function getgroup($id)
    {
        self::initialize();
        $query = "SELECT r.id AS id, r.name AS name, r.users AS users, rm.parent_id AS parentid 
                  FROM " . self::$rolestable . " r LEFT JOIN " . self::$rolememberstable . " rm ON r.id = rm.role_id 
                  WHERE role_id = ? AND r.itemtype = ? AND r.state = ? ORDER BY r.name";
        $bindvars[] = $id;
        $bindvars[] = ROLES_GROUPTYPE;
        $bindvars[] = ROLES_STATE_ACTIVE;
        $dbconn = xarDB::getConn();
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
        if(!$result) return;            
        while($result->next() > 0) $group[] = $result->fields;
        if (!empty($group)) return $group;
        return false;
    }

    /**
     * getsubgroups: get the children of a group that are groups themselves
     *
     * This function is useful for setting up trees
     * We don't include users in the tree because there are too many to display
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param int $id
     * @return array representing the subgroups of a group
     */
    public static function getsubgroups($id)
    {
        $subgroups = array();
        $groups = self::getgroups();
        foreach($groups as $subgroup) {
            if ($subgroup['parentid'] == $id) {
                $subgroups[] = $subgroup;
            }
        }
        return $subgroups;
    }

    /**
     * get: gets a single role
     *
     * Retrieves a single role (user or group) from the roles repository
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param integer $id
     * @return object role
     */
    public static function get($id)
    {
        $cacheKey = 'Roles.ById';
        if(xarVarIsCached($cacheKey,$id)) {
            return xarVarGetCached($cacheKey,$id);
        }
        // Need to get it from DB.
        // TODO: move caching to _lookuprole?
        $r = self::_lookuprole('id',(int) $id);
        xarVarSetCached($cacheKey,$id,$r);
        return $r;
    }

    /**
     * Wrapper functions to support Xaraya 1 API for roles
     */
    public static function getRole($id) {return self::get($id);}

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
        return self::_lookuprole('name',$name,$state=ROLES_STATE_ACTIVE);
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
        return self::_lookuprole('uname',$uname,$state=ROLES_STATE_ACTIVE);
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
        $query = "SELECT * FROM " . self::$rolestable . " WHERE name = ?";
        // Prepare it once
        $stmt = self::$dbconn->prepareStatement($query);

        // Execute the query, bail if an exception was thrown
        $result = $stmt->executeQuery(array($parentname));
        $result->first();

        // create the parent object
        list($id, $name, $itemtype, $parentid, $uname, $email, $pass,
            $date_reg, $val_code, $state, $auth_module) = $result->fields;
        sys::import('modules.dynamicdata.class.objects.master');
        $parent = DataObjectMaster::getObject(array('class' => 'Role', 'module' => 'roles', 'itemtype' => $itemtype));
        $parent->getItem(array('itemid' => $id));

        // retrieve the child's data from the repository
        // Execute the query, bail if an exception was thrown
        $result = $stmt->executeQuery(array($childname));
        $result->first();

        // create the child object
        list($id, $name, $itemtype, $parentid, $uname, $email, $pass,
            $date_reg, $val_code, $state, $auth_module) = $result->fields;
        sys::import('modules.roles.class.role');
        $child = DataObjectMaster::getObject(array('class' => 'Role', 'module' => 'roles', 'itemtype' => $itemtype));
        $child->getItem(array('itemid' => $id));

       // done
        return $parent->addMember($child);
    }

    /**
     * _lookuprole : Lookup a row based upon a specified field
     *
     * @param string $field
     * @param mixed  $value
     * @param int    $state
     * @return object a role
     */
    private static function _lookuprole($field,$value,$itemtype=ROLES_USERTYPE,$state=ROLES_STATE_ALL)
    {
        // retrieve the object's data from the repository
        // set up and execute the query
        self::initialize();
        sys::import('xaraya.structures.query');
        $q = new Query('SELECT',self::$rolestable);
        $q->eq($field,$value);
        if ($state == ROLES_STATE_CURRENT) {
            $q->ne('state',ROLES_STATE_DELETED);
        } elseif ($state != ROLES_STATE_ALL) {
            $q->eq('state',$state);
        }

        // Execute the query, bail if an exception was thrown
        if (!$q->run()) return;

        // set the data in an array
        $row = $q->row();
        if (empty($row)) return;

        $duvarray = array('userhome','primaryparent','passwordupdate','userlastlogin','usertimezone');
        $duvs = array();
        foreach ($duvarray as $key) {
            $duv = xarModUserVars::Get('roles',$key,$row['id']);
            if (!empty($duv)) $duvs[$key] = $duv;
        }
        // create and return the role object
        sys::import('modules.roles.class.role');
        $role = DataObjectMaster::getObject(array('class' => 'Role', 'module' => 'roles', 'itemtype' => $row['itemtype']));
        $role->getItem(array('itemid' => $row['id']));
        return $role;
    }
}
?>