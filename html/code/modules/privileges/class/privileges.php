<?php
/**
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 *
 * xarPrivileges: class for the privileges repository
 *
 * Represents the repository containing all privileges
 * The constructor is the constructor of the parent object
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
*/

sys::import('modules.privileges.class.masks');

class xarPrivileges extends xarMasks
{

    /**
     * defineInstance: define how a module's instances are registered
     *
     * Creates an entry in the instances table
     * This function should be invoked at module initialisation time
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @param   string $module
     * @return  boolean
     * @todo remove table2 argument
     */
    public static function defineInstance($module,$type,$instances,$propagate=0,$table2='',$childID='',$parentID='',$description='')
    {
        parent::initialize();
        foreach($instances as $instance) {
            // make privilege wizard URLs relative, for easier migration of sites
            if (!empty($instance['header']) && $instance['header'] == 'external' && !empty($instance['query'])) {
                $base = xarServer::getBaseURL();
                $instance['query'] = str_replace($base,'',$instance['query']);
            }

            // Check if the instance already exists.
            // The instance is uniquely defined by its module, component and header.
            // FIXME: since the header is just a label, it probably should not be
            // treated as key information here. Do we need some further unique (within a
            // module and component) name for an instance, independant of the header label?
            $iTable = parent::$instancestable; $mTable = parent::$modulestable;
            $query = "SELECT instances.id
                      FROM   $iTable instances, $mTable mods
                      WHERE  instances.module_id = mods.id AND
                             mods.name = ? AND
                             instances.component = ? AND
                             instances.header = ?";
            $stmt = parent::$dbconn->prepareStatement($query);
            $result = $stmt->executeQuery(array($module, $type, $instance['header']));

            try {
                parent::$dbconn->begin();
                if ($result->first()) {
                    // Instance exists: update it.
                    list($id) = $result->fields;
                    $query = "UPDATE $iTable
                          SET query = ?, ddlimit = ?,
                              description = ?
                          WHERE id = ?";
                    $bindvars = array(
                                      $instance['query'], $instance['limit'],
                                      $description, $id
                                      );
                } else {
                    $query = "INSERT INTO $iTable
                          ( module_id, component, header,
                            query, ddlimit, description)
                          VALUES (?,?,?,?,?,?)";
                    $modInfo = xarMod::getBaseInfo($module);
                    $module_id = $modInfo['systemid'];
                    $bindvars = array(
                                      $module_id, $type, $instance['header'],
                                      $instance['query'], $instance['limit'],
                                      $description
                                      );
                }
                $stmt = parent::$dbconn->prepareStatement($query);
                $stmt->executeUpdate($bindvars);
                parent::$dbconn->commit();
            } catch (SQLException $e) {
                parent::$dbconn->rollback();
                throw $e;
            }
        }
        return true;
    }

    /**
     * removeInstances: remove the instances registered by a module form the database
     * *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   string $module name
     * @return  boolean
    */
    public static function removeInstances($module)
    {
        parent::initialize();
        try {
            parent::$dbconn->begin();
            $modInfo = xarMod::getBaseInfo($module);
            $module_id = $modInfo['systemid'];
            $query = "DELETE FROM " . parent::$instancestable . " WHERE module_id = ?";
            //Execute the query, bail if an exception was thrown
            parent::$dbconn->Execute($query,array($module_id));
            parent::$dbconn->commit();
        } catch (SQLException $e) {
            parent::$dbconn->rollback(); // redundant? we need to investigate concurency and locking
            throw $e;
        }
        return true;
    }

    /**
     * register: register a privilege
     *
     * Creates an entry in the privileges table
     * This function should be invoked every time a new instance is created
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   string $name
     * @return  boolean
     * @todo    duplicates parts of $privilege->add() method
    */
    public static function register($name,$realm,$module,$component,$instance,$level,$description='')
    {
        parent::initialize();
        // Check if the privilege already exists
        $privilege = self::findPrivilege($name);
        if ($privilege) {
            return true;
        }

        $realmid = null;
        if($realm != 'All') {
            $stmt = parent::$dbconn->prepareStatement('SELECT id FROM '.parent::$realmstable .' WHERE name=?');
            $result = $stmt->executeQuery(array($realm),xarDB::FETCHMODE_ASSOC);
            if($result->next()) $realmid = $result->getInt('id');
        }
        if($module == 'All') {
            $module_id = self::PRIVILEGES_ALL;
        } elseif($module == null) {
            $module_id = null;
        } else {
            $module_id = xarMod::getID($module);
        }
        if (is_string($level)) {
            $level = xarSecurity::getLevel($level);
        }
        $query = "INSERT INTO " . parent::$privilegestable . " (
                    name, realm_id, module_id, component,
                    instance, level, description, itemtype)
                  VALUES (?,?,?,?,?,?,?,?)";
        $bindvars = array($name, $realmid, $module_id, $component,
                          $instance, $level, $description, parent::PRIVILEGES_PRIVILEGETYPE);

        parent::$dbconn->Execute($query,$bindvars);
        return true;
    }

    /**
     * assign: assign a privilege to a user/group
     *
     * Creates an entry in the acl table
     * This is a convenience function that can be used by module developers
     * Note the input params are strings to make it easier.
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   string
     * @param   string
     * @return  boolean
    */
    public static function assign($privilegename,$rolename)
    {
        parent::initialize();
        // get the ID of the privilege to be assigned
        $privilege = self::findPrivilege($privilegename);
        $privid = $privilege->getID();

        // find the role for the assignation and get its ID
        $role = xarRoles::findRole($rolename);
        $roleid = $role->getID();

        $bindvars = array($roleid,$privid);
        
        // Check if the privilege already exists
        $query = "SELECT * FROM " . parent::$acltable . " WHERE role_id = ? and privilege_id = ?";
        $stmt = parent::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if ($result->first()) return true;
        
        // Add the assignation as an entry to the acl table
        $query = "INSERT INTO " . parent::$acltable . " VALUES (?,?)";
        parent::$dbconn->Execute($query,$bindvars);

        // empty the privset cache
        //        parent::$forgetprivsets();

        // Refresh the privileges cached for the current sessions
        xarMasks::clearCache();
        return true;
    }

    public static function getAssignments(Array $args=array())
    {
        parent::initialize();

        $where = "WHERE p.itemtype = " . self::PRIVILEGES_PRIVILEGETYPE;
        if (!empty($args['privilege_id']))      $where .= ' AND p.id = ' . $args['privilege_id'];
        if (!empty($args['role_id']))      $where .= ' AND r.id = ' . $args['role_id'];
        if (!empty($args['module'])) {
            if ($args['module'] == strtolower('All')) $where .= " AND p.module_id = " . 0;
            else $where .= " AND p.module_id = " . xarMod::getID($args['module']);
        }
        $query = "SELECT p.id, p.name, r.id AS role_id,r.itemtype,r.name AS role_name,
                         p.module_id, p.component, p.instance,
                         p.level,  p.description
                  FROM " . parent::$privilegestable . " p INNER JOIN ". parent::$acltable . " a ON p.id = a.privilege_id
                  INNER JOIN ". parent::$rolestable . " r ON a.role_id = r.id " .
                  $where .
                  " ORDER BY p.name";
        $stmt = parent::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array());
        $allprivileges = array();
        while($result->next()) {
            list($id, $name, $role_id, $role_type, $role_name, $module, $component, $instance, $level,
                    $description) = $result->fields;
            $allprivileges[] = array('id' => $id,
                               'name' => $name,
                               'role_id' => $role_id,
                               'role_type' => $role_type,
                               'role_name' => $role_name,
                               'module' => $module,
                               'component' => $component,
                               'instance' => $instance,
                               'level' => $level,
                               'description' => $description);
        }
        return $allprivileges;
    }
    /**
     * getprivileges: returns all the current privileges.
     *
     * Returns an array of all the privileges in the privileges repository
     * The repository contains an entry for each privilege.
     * This function will initially load the privileges from the db into an array and return it.
     * On subsequent calls it just returns the array .
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return array<mixed> of privileges
     * @todo    use associative fetching and one getrow statement.
    */
    public static function getprivileges(Array $args=array())
    {
        parent::initialize();

        xarLog::message('PRIV: getting all privileges, once!', xarLog::LEVEL_INFO);
        $where = "WHERE itemtype = " . self::PRIVILEGES_PRIVILEGETYPE;
        if (!empty($args['name']))      $where .= ' AND p.name = ' . $args['name'];
        if (!empty($args['module'])) {
            if ($args['module'] == strtolower('All')) $where .= " AND p.module_id = " . 0;
            else $where .= " AND p.module_id = " . xarMod::getID($args['module']);
        }
        if (!empty($args['component'])) $where .= ' AND m.component = ' . $args['component'];
        $query = "SELECT p.id, p.name, r.name AS realm,
                         m.name AS module, p.component, p.instance,
                         p.level,  p.description
                  FROM " . parent::$privilegestable . " p LEFT JOIN ". parent::$realmstable . " r ON p.realm_id = r.id
                  LEFT JOIN ". parent::$modulestable . " m ON p.module_id = m.id " .
                  $where .
                  " ORDER BY p.name";
        $stmt = parent::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array());
        $allprivileges = array();
        while($result->next()) {
            list($id, $name, $realm, $module, $component, $instance, $level,
                    $description) = $result->fields;
            $allprivileges[] = array('id' => $id,
                               'name' => $name,
                               'realm' => is_null($realm) ? 'All' : $realm,
                               'module' => $module,
                               'component' => $component,
                               'instance' => $instance,
                               'level' => $level,
                               'description' => $description);
        }
        return $allprivileges;

    }

    /**
     * gettoplevelprivileges: returns all the current privileges that have no parent.
     *
     * Returns an array of all the privileges in the privileges repository
     * that are top level entries, i.e. have no parent
     * This function will initially load the privileges from the db into an array and return it.
     * On subsequent calls it just returns the array .
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   string $arg indicates what types of elements to get
     * @return array<mixed> of privileges
     * @todo    use associative fetching and one getrow
     * @todo    cache with statics?
    */
    public static function gettoplevelprivileges($arg)
    {
        parent::initialize();
        // Base query
        $query = "SELECT DISTINCT p.id, p.name,  r.name AS realm,
                         p.module_id,  p.component, p.instance,
                         p.level, p.description, pm.parent_id
                  FROM " . parent::$privilegestable . " p LEFT JOIN " .
                           parent::$privmemberstable  . " pm ON p.id = pm.privilege_id LEFT JOIN " . parent::$realmstable . " r ON p.realm_id = r.id";

        if($arg == "all") {
             $query .= " WHERE pm.parent_id IS NULL ";
        } elseif ($arg == "assigned") {
            $query .= ", " . self::$acltable . " acl
                        WHERE p.id = acl.privilege_id AND
                              pm.parent_id IS NULL ";
        } elseif ($arg == "unassigned") {
            $query .= " LEFT JOIN " . self::$acltable . " acl
                        ON p.id = acl.privilege_id WHERE
                              pm.parent_id IS NULL AND acl.privilege_id IS NULL ";
        }
        $query .=" AND p.itemtype = ?";
        $query .=" ORDER BY p.name";

        $stmt = parent::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array(self::PRIVILEGES_PRIVILEGETYPE));

        $privileges = array();
        $pids = array();
        while($result->next()) {
            list($id, $name, $realm, $module, $component, $instance, $level,
                    $description,$parentid) = $result->fields;
            $thisone = $id;
            if (!in_array($thisone,$pids)) {
                $pids[] = $thisone;
                $privileges[] = array(
                    'id'         => $id,
                    'name'        => $name,
                    'realm'       => is_null($realm) ? 'All' : $realm,
                    'module'      => $module,
                    'component'   => $component,
                    'instance'    => $instance,
                    'level'       => $level,
                    'description' => $description,
                    'parentid'    => $parentid
                );
            }
        }
        $alltoplevelprivileges = $privileges;
        return $privileges;
    }

    /**
     * getrealms: returns all the current realms.
     *
     * Returns an array of all the realms in the realms table
     * They are used to populate dropdowns in displays
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return array<mixed> of realm ids and names
     * @todo    this isn't really the right place for this function
    */
    public static function getrealms()
    {
        parent::initialize();
        static $allreams = array(); // Get them once

        if (empty($allrealms)) {
            $query = "SELECT id, name FROM " . parent::$realmstable;
            $stmt = parent::$dbconn->prepareStatement($query);
            $result = $stmt->executeQuery();

            // add some extra lines we want
            // $allrealms[] = array('id' => -2,'name' => ' ');
            $allrealms[] = array('id' => -1,'name' => 'All');
            // $allrealms[] = array('id' => 0, 'name' => 'None');

            // add the realms from the database
            while($result->next()) {
                $allrealms[] = array(
                    'id' => $result->getInt(1),
                    'name' => $result->getString(2)
                );
            }
        }
        return $allrealms;
    }

    /**
     * getmodules: returns all the current modules.
     *
     * Returns an array of all the modules in the modules table
     * They are used to populate dropdowns in displays
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return array<mixed> of module ids and names
     * @todo    this isn't really the right place for this function
     * @todo    ucfirst is a presentation issue.
     */
    public static function getmodules()
    {
        parent::initialize();
        static $allmodules = array();

        if (empty($allmodules)) {
            $query = "SELECT modules.id, modules.name
                      FROM " . parent::$modulestable . " modules
                      WHERE modules.state = ?
                      ORDER BY modules.name";
            $stmt = parent::$dbconn->prepareStatement($query);
            $result = $stmt->executeQuery(array(3));

            // add some extra lines we want
            // $allmodules[] = array('id' => -2, 'name' => ' ');
            $allmodules[] = array('id' => -1,'name' => 'All','display' => 'All');
            // $allmodules[] = array('id' => 0, 'name' => 'None');
            // add the modules from the database
            // TODO: maybe remove the key, don't really need it
            while($result->next()) {
                $allmodules[] = array(
                    'id'   => $result->getInt(1),
                    'name' => $result->getString(2),
                     //'display' => xarMod::getDisplayName($name),
                    'display' => ucfirst($result->getString(2))
                );
            }
        }
        return $allmodules;
    }

    public static function getprivilegefast($id)
    {
        foreach(self::getprivileges() as $privilege){
            if ($privilege['id'] == $id) return $privilege;
        }
        return false;
    }

    /**
     * getPrivilege: gets a single privilege
     *
     * Retrieves a single privilege object from the Privileges repository
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   integer
     * @return  xarPrivilege|void object
    */
    public static function getPrivilege($id)
    {
        parent::initialize();

        $cacheKey = 'Privilege.ByPid';
        if(xarCoreCache::isCached($cacheKey,$id)) {
            return xarCoreCache::getCached($cacheKey,$id);
        }
        // Need to get it
        $query = "SELECT p.id, p.name, r.name, p.module_id, m.name, p.component, p.instance, p.level, p.description
                  FROM " . parent::$privilegestable . " p LEFT JOIN ". parent::$realmstable ." r ON p.realm_id = r.id
                  LEFT JOIN ". parent::$modulestable ." m ON p.module_id = m.id
                  WHERE itemtype = ?";
        if(is_numeric($id)) $query .= " AND p.id = ?";
        else  $query .= " AND p.name = ?";

        $stmt = parent::$dbconn->prepareStatement($query);
        //Execute the query, bail if an exception was thrown
        $result = $stmt->executeQuery(array(self::PRIVILEGES_PRIVILEGETYPE,$id),xarDB::FETCHMODE_NUM);

        if ($result->next()) {
            list($id,$name,$realm,$module_id,$module,$component,$instance,$level,$description) = $result->fields;
            $pargs = array('id'=>$id,
                           'name'=>$name,
                           'realm'=> is_null($realm) ? 'All' : $realm,
                           'module'=>$module,
                           'module_id'=>$module_id,
                           'component'=>$component,
                           'instance'=>$instance,
                           'level'=>$level,
                           'description'=>$description,
                           'parentid'=>0);

            sys::import('modules.privileges.class.privilege');
            $priv = new xarPrivilege($pargs);
            xarCoreCache::setCached($cacheKey,$id,$priv);
            return $priv;
        } else {
            return;
        }
    }

    /**
     * findPrivilege: finds a single privilege based on its name
     *
     * Retrieves a single privilege object from the Privileges repository
     * This is a convenience class for module developers
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   string
     * @return  xarPrivilege|void object
    */
    public static function findPrivilege($name)
    {
        parent::initialize();

        // @fixme specify the columns we want
        $query = "SELECT p.*, m.name FROM " . parent::$privilegestable . " p
        LEFT JOIN ". parent::$modulestable ." m ON p.module_id = m.id WHERE p.itemtype = ? AND p.name = ?";
        $stmt = parent::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array(self::PRIVILEGES_PRIVILEGETYPE, $name));

        if ($result->first()) {
            list($id,$name,$realm,$module_id,$component,$instance,$level,$description,$itemtype,$module) = $result->fields;
            $pargs = array('id'=>$id,
                           'name'=>$name,
                           'realm'=>$realm,
                           'module'=>$module,
                           'module_id'=>$module_id,
                           'component'=>$component,
                           'instance'=>$instance,
                           'level'=>$level,
                           'description'=>$description,
                           'parentid'=>0);
            sys::import('modules.privileges.class.privilege');
            return new xarPrivilege($pargs);
        }
        return;
    }

    /**
     * findPrivilegesForModule: finds the privileges assigned to a module
     *
     * Retrieves an of privilege objects from the Privileges repository
     * This is a convenience class for module developers
     *
     * @author  Richard Cave<rcave@xaraya.com>
     * @access  public
     * @param   string
     * @return array<mixed> of xarPrivilege objects
    */
    public static function findPrivilegesForModule($module)
    {
        static $stmt = null; // only prepare it once

        parent::initialize();
        $privileges = array();
        // @fixme specify the columns we want
        $query = "SELECT p.*, m.name FROM " . parent::$privilegestable . " p
        LEFT JOIN ". parent::$modulestable ." m ON p.module_id = m.id WHERE p.itemtype = ? AND p.module_id = ?";
        //Execute the query, bail if an exception was thrown
        if(!isset($stmt)) $stmt = parent::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array(self::PRIVILEGES_PRIVILEGETYPE, xarMod::getID($module)));
        while ($result->next()) {
            list($id,$name,$realm,$module_id,$component,$instance,$level,$description,$itemtype,$module) = $result->fields;
            $pargs = array(
                'id'         => $id,
                'name'        => $name,
                'realm'       => $realm,
                'module'      => $module,
                'module_id'   => $module_id,
                'component'   => $component,
                'instance'    => $instance,
                'level'       => $level,
                'description' => $description,
                'parentid'    => 0
            );
            $privileges[] = new xarPrivilege($pargs);
        }
        // Close result set
        $result->Close();
        return $privileges;
    }

    /**
     * removeModule: removes the privileges registered by a module from the database
     *
     * This is a wrapper function
     *
     * @param   string $module
     * @return  void
     */
    public static function removeModule($module)
    {
        // Get the pids for the module
        $modulePrivileges = self::findPrivilegesForModule($module);
        foreach ($modulePrivileges as $modulePrivilege) {
            $modulePrivilege->remove();
        }
    }

    /**
     * makeMember: makes a privilege a child of another privilege
     *
     * Creates an entry in the privmembers table
     * This is a convenience class for module developers
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   string
     * @param   string
     * @return  boolean
     * @todo    create exceptions for bad input
    */
    public static function makeMember($childname,$parentname)
    {
        $parent = self::findPrivilege($parentname);
        $child = self::findPrivilege($childname);
        if ($child->isParentPrivilege($parent)) return true;
        return $parent->addMember($child);
    }

    /**
     * external: stores a privilege from an external wizard in the repository.
     *
     * This is a wrapper function
     *
     * @param   integer pid,level
     * @param   string name,realm,module,component
     * @param   array instance
     * @return  mixed
     */
    public static function external($pid,$name,$realm,$module,$component,$instance,$level)
    {
        // from xarMod::apiFunc('privileges','admin','returnprivilege',array(...));
        if (!empty($instance) && is_array($instance)) {
            $instance = implode(':',$instance);
        }
        $instance = !empty($instance) ? $instance : "All";

        if(empty($pid)) {
            $pargs = array('name' => $name,
                           'realm' => $realm,
                           'module' => $module,
                           'module_id'=>xarMod::getID($module),
                           'component' => $component,
                           'instance' => $instance,
                           'level' => $level,
                           'parentid' => 0
                           );
            sys::import('modules.privileges.class.privilege');
            $priv = new xarPrivilege($pargs);
            if ($priv->add()) return $priv->getID();
        } else {
            $priv = self::getPrivilege($pid);
            $priv->setName($name);
            $priv->setRealm($realm);
            $priv->setModule($module);
            $priv->setModuleID($module);
            $priv->setComponent($component);
            $priv->setInstance($instance);
            $priv->setLevel($level);
            if ($priv->update()) return $priv->getID();
        }
    }
}
