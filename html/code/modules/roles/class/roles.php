<?php
/**
 * xarRoles class
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */

/**
 * xarRoles: class for the role repository
 *
 * Represents the repository containing all roles
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 */
class xarRoles extends xarObject
{
    const ROLES_STATE_DELETED = 0;
    const ROLES_STATE_INACTIVE = 1;
    const ROLES_STATE_NOTVALIDATED = 2;
    const ROLES_STATE_ACTIVE = 3;
    const ROLES_STATE_PENDING = 4;
    const ROLES_STATE_CURRENT = 98;
    const ROLES_STATE_ALL = 99;

    const ROLES_USERTYPE = 1;
    const ROLES_GROUPTYPE = 2;

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
     * @return array<mixed>|void of arrays representing all the groups
     */
    public static function getgroups()
    {
        self::initialize();
        static $allgroups = array();
        if (empty($allgroups)) {
            $query = "SELECT r.id AS id, r.name AS name, r.users AS users, rm.parent_id AS parentid 
                      FROM " . self::$rolestable . " r LEFT JOIN " . self::$rolememberstable . " rm ON r.id = rm.role_id 
                      WHERE r.itemtype = ? AND r.state = ? ORDER BY r.name";
            $bindvars[] = self::ROLES_GROUPTYPE;
            $bindvars[] = self::ROLES_STATE_ACTIVE;
            $dbconn = xarDB::getConn();
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars, xarDB::FETCHMODE_ASSOC);
            if(!$result) return;            
            while($result->next()) $allgroups[] = $result->fields;
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
     * @return array<mixed>|void representing the group
     */
    public static function getgroup($id)
    {
        self::initialize();
        $query = "SELECT r.id AS id, r.name AS name, r.users AS users, rm.parent_id AS parentid 
                  FROM " . self::$rolestable . " r LEFT JOIN " . self::$rolememberstable . " rm ON r.id = rm.role_id 
                  WHERE role_id = ? AND r.itemtype = ? AND r.state = ? ORDER BY r.name";
        $bindvars[] = $id;
        $bindvars[] = self::ROLES_GROUPTYPE;
        $bindvars[] = self::ROLES_STATE_ACTIVE;
        $dbconn = xarDB::getConn();
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars, xarDB::FETCHMODE_ASSOC);
        if(!$result) return;            
        while($result->next()) $group[] = $result->fields;
        if (!empty($group)) return $group;
        return;
    }

    /**
     * getsubgroups: get the children of a group that are groups themselves
     *
     * This function is useful for setting up trees
     * We don't include users in the tree because there are too many to display
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param int $id
     * @return array<mixed> representing the subgroups of a group
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
        if(xarVar::isCached($cacheKey,$id)) {
            return xarVar::getCached($cacheKey,$id);
        }
        // Need to get it from DB.
        // TODO: move caching to _lookuprole?
        $r = self::_lookuprole('id',(int) $id);
        xarVar::setCached($cacheKey,$id,$r);
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
    public static function findRole($name,$itemtype=self::ROLES_USERTYPE,$state=self::ROLES_STATE_ACTIVE)
    {
        return self::_lookuprole('name',$name,$itemtype,$state);
    }

    /**
     * ufindRole: finds a single role based on its username
     *
     * @param string $uname
     * @return object role
     * @todo cache this too?
     */
    public static function ufindRole($uname,$itemtype=self::ROLES_USERTYPE,$state=self::ROLES_STATE_ACTIVE)
    {
        return self::_lookuprole('uname',$uname,$itemtype,$state);
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
     * @return boolean
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
        sys::import('modules.dynamicdata.class.objects.factory');
        switch ($itemtype) {
            case 1: $name = "roles_users"; break;
            case 2: $name = "roles_groups"; break;
        }
        /** @var Role $parent */
        $parent = DataObjectFactory::getObject(array('name' => $name));
        $parent->getItem(array('itemid' => $id));

        // retrieve the child's data from the repository
        // Execute the query, bail if an exception was thrown
        $result = $stmt->executeQuery(array($childname));
        $result->first();

        // create the child object
        list($id, $name, $itemtype, $parentid, $uname, $email, $pass,
            $date_reg, $val_code, $state, $auth_module) = $result->fields;
        sys::import('modules.roles.class.role');
        switch ($itemtype) {
            case 1: $name = "roles_users"; break;
            case 2: $name = "roles_groups"; break;
        }
        /** @var Role $child */
        $child = DataObjectFactory::getObject(array('name' => $name));
        $child->getItem(array('itemid' => $id));

       // done
        return $parent->addMember($child);
    }

    /**
     * makeMemberByUname: create a parent-child relationship in the database between two roles
     *
     * This is a wrapper function
     *
     * @param  string child uname
     * @param  string parent uname
     * @return boolean
     */
    public static function makeMemberByUname($childName, $parentName)
    {
        $parent = self::ufindRole($parentName);
        $child = self::ufindRole($childName);

        return $parent->addMember($child);
    }

    /**
     * makeMemberByID: create a parent-child relationship in the database between two roles
     *
     * This is a wrapper function
     *
     * @param  string child ID
     * @param  string parent ID
     * @return boolean
     */
    public static function makeMemberByID($childId, $parentId)
    {
        $parent = self::getRole($parentId);
        $child = self::getRole($childId);

        return $parent->addMember($child);
    }

    /**
     * removeMemberByID: destroys a parent-child relationship in the database between two roles
     *
     * This is a wrapper function
     *
     * @param  string child ID
     * @param  string parent ID
     * @return boolean
     */
    public static function removeMemberByID($childId, $parentId)
    {
        $parent = self::getRole($parentId);
        $child = self::getRole($childId);

        return $parent->removeMember($child);
    }

    public static function current()
    {
        return self::getRole(xarSession::getVar('role_id'));
    }

    public static function isParent($name1, $name2)
    {
        $role1 = self::findRole($name1);
        $role2 = self::ufindRole($name2);
        if (is_object($role1) && is_object($role2)) {
            return $role2->isParent($role1);
        }
        return false;
    }

    public static function isAncestor($name1, $name2)
    {
        $role1 = self::findRole($name1);
        $role2 = self::ufindRole($name2);
        if (is_object($role1) && is_object($role2)) {
            return $role2->isAncestor($role1);
        }
        return false;
    }

    /**
     * _lookuprole : Lookup a row based upon a specified field
     *
     * @param string $field
     * @param mixed  $value
     * @param int    $state
     * @return object|void a role
     */
    private static function _lookuprole($field,$value,$itemtype=self::ROLES_USERTYPE,$state=self::ROLES_STATE_ALL)
    {
        // get rid of 30 repeating queries for base homepage due to security checks
        $cacheScope = 'Roles.ByLookup';
        $cacheName = "$field:$value:$itemtype:$state";
        if (xarCoreCache::isCached($cacheScope, $cacheName)) {
            $row = xarCoreCache::getCached($cacheScope, $cacheName);
        } else {
            // retrieve the object's data from the repository
            // set up and execute the query
            self::initialize();
            $query = "SELECT * FROM " . self::$rolestable . " WHERE $field = ?";
            $params = [$value];

            if ($state == self::ROLES_STATE_CURRENT) {
                $query .= " AND state != ?";
                $params[] = self::ROLES_STATE_DELETED;
            } elseif ($state != self::ROLES_STATE_ALL) {
                $query .= " AND state = ?";
                $params[] = $state;
            }
            $stmt = self::$dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($params, xarDB::FETCHMODE_ASSOC);
            if(!$result) return;
            if($result->next()) $row = $result->fields;
            if (empty($row)) return;
            xarCoreCache::setCached($cacheScope, $cacheName, $row);
        }

        // create and return the role object
        if ($row['itemtype'] == self::ROLES_USERTYPE) $name = 'roles_users';
        elseif ($row['itemtype'] == self::ROLES_GROUPTYPE) $name = 'roles_groups';
        else throw new Exception(xarML('Unknown role type'));
        $cacheKey = 'Roles.ById';
        if(xarVar::isCached($cacheKey,$row['id'])) {
            return xarVar::getCached($cacheKey,$row['id']);
        }
        sys::import('modules.dynamicdata.class.objects.factory');
        $role = DataObjectFactory::getObject(array('name' => $name));
        $role->getItem(array('itemid' => $row['id']));
        xarVar::setCached($cacheKey,$row['id'],$role);
        return $role;
    }
}
