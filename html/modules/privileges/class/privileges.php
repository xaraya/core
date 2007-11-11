<?php
/**
 * xarPrivileges: class for the privileges repository
 *
 * Represents the repository containing all privileges
 * The constructor is the constructor of the parent object
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @throws  none
 * @todo    none
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
     * @access  public
     * @param   array of values to register instance
     * @return  boolean
    */
    public static function defineInstance($module,$type,$instances,$propagate=0,$table2='',$childID='',$parentID='',$description='')
    {
        foreach($instances as $instance) {
            // make privilege wizard URLs relative, for easier migration of sites
            if (!empty($instance['header']) && $instance['header'] == 'external' && !empty($instance['query'])) {
                $base = xarServerGetBaseURL();
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
                              propagate = ?, instancetable2 = ?, instancechildid = ?,
                              instanceparentid = ?, description = ?
                          WHERE id = ?";
                    $bindvars = array(
                                      $instance['query'], $instance['limit'],
                                      $propagate, $table2, $childID, $parentID,
                                      $description, $id
                                      );
                } else {
                    $query = "INSERT INTO $iTable
                          ( module_id, component, header,
                            query, ddlimit, propagate,
                            instancetable2, instancechildid,
                            instanceparentid, description)
                          VALUES (?,?,?,?,?,?,?,?,?,?)";
                    $modInfo = xarMod_GetBaseInfo($module);
                    $module_id = $modInfo['systemid'];
                    $bindvars = array(
                                      $module_id, $type, $instance['header'],
                                      $instance['query'], $instance['limit'],
                                      $propagate, $table2, $childID, $parentID,
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
     * @param   module name
     * @return  boolean
     * @throws  none
     * @todo    none
    */
    public static function removeInstances($module)
    {
        parent::initialize();
        try {
            parent::$dbconn->begin();
            $modInfo = xarMod_GetBaseInfo($module);
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
     * @param   array of privilege values
     * @return  boolean
     * @throws  none
     * @todo    duplicates parts of $privilege->add() method
    */
    public static function register($name,$realm,$module,$component,$instance,$level,$description='')
    {
        parent::initialize();

        $realmid = null;
        if($realm != 'All') {
            $stmt = parent::$dbconn->prepareStatement('SELECT id FROM '.parent::$realmstable .' WHERE name=?');
            $result = $stmt->executeQuery(array($realm),ResultSet::FETCHMODE_ASSOC);
            if($result->next()) $realmid = $result->getInt('id');
        }
        if($module == 'All') {
            $module_id = self::PRIVILEGES_ALL;
        } elseif($module == null) {
            $module_id = null;
        } else {
            $module_id = xarMod::getID($module);
        }
        $query = "INSERT INTO " . parent::$privilegestable . " (
                    name, realmid, module_id, component,
                    instance, level, description, type)
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
     * @throws  none
     * @todo    none
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

        // Add the assignation as an entry to the acl table
        $query = "INSERT INTO " . parent::$acltable . " VALUES (?,?)";
        $bindvars = array($roleid,$privid);
        parent::$dbconn->Execute($query,$bindvars);

        // empty the privset cache
        //        parent::$forgetprivsets();

        return true;
    }

    public static function getAssignments(Array $args=array())
    {
        parent::initialize();

		$where = "WHERE p.type = " . self::PRIVILEGES_PRIVILEGETYPE;
		if (!empty($args['privilege_id']))      $where .= ' AND p.id = ' . $args['privilege_id'];
		if (!empty($args['role_id']))      $where .= ' AND r.id = ' . $args['role_id'];
		if (!empty($args['module'])) {
			if ($args['module'] == strtolower('All')) $where .= " AND p.module_id = " . 0;
			else $where .= " AND p.module_id = " . xarMod::getID($args['module']);
		}
		$query = "SELECT p.id, p.name, r.id,r.type,r.name,
						 p.module_id, p.component, p.instance,
						 p.level,  p.description
				  FROM " . parent::$privilegestable . " p INNER JOIN ". parent::$acltable . " a ON p.id = a.permid
				  INNER JOIN ". parent::$rolestable . " r ON a.partid = r.id " .
				  $where .
				  " ORDER BY p.name";
		$stmt = parent::$dbconn->prepareStatement($query);
		// The fetchmode *needed* to be here, dunno why. Exception otherwise
		$result = $stmt->executeQuery($query,ResultSet::FETCHMODE_NUM);
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
     * @param   none
     * @return  array of privileges
     * @throws  none
     * @todo    use associative fetching and one getrow statement.
    */
    public static function getprivileges(Array $args=array())
    {
        parent::initialize();

		xarLogMessage('PRIV: getting all privs, once!');
		$where = "WHERE type = " . self::PRIVILEGES_PRIVILEGETYPE;
		if (!empty($args['name']))      $where .= ' AND p.name = ' . $args['name'];
		if (!empty($args['module'])) {
			if ($args['module'] == strtolower('All')) $where .= " AND p.module_id = " . 0;
			else $where .= " AND p.module_id = " . xarMod::getID($args['module']);
		}
		if (!empty($args['component'])) $where .= ' AND m.component = ' . $args['component'];
		$query = "SELECT p.id, p.name, r.name,
						 m.name, p.component, p.instance,
						 p.level,  p.description
				  FROM " . parent::$privilegestable . " p LEFT JOIN ". parent::$realmstable . " r ON p.realmid = r.id
				  LEFT JOIN ". parent::$modulestable . " m ON p.module_id = m.id " .
				  $where .
				  " ORDER BY p.name";
		$stmt = parent::$dbconn->prepareStatement($query);
		// The fetchmode *needed* to be here, dunno why. Exception otherwise
		$result = $stmt->executeQuery($query,ResultSet::FETCHMODE_NUM);
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
     * @return  array of privileges
     * @throws  none
     * @todo    use associative fetching and one getrow
     * @todo    cache with statics?
    */
    public static function gettoplevelprivileges($arg)
    {
        parent::initialize();
        // Base query
        $query = "SELECT DISTINCT p.id, p.name,  r.name,
                         p.module_id,  p.component, p.instance,
                         p.level, p.description, pm.parentid
                  FROM " . parent::$privmemberstable . " pm, " .
                           parent::$privilegestable  . " p LEFT JOIN " . parent::$realmstable . " r ON p.realmid = r.id";

        if($arg == "all") {
             $query .= " WHERE p.id = pm.id AND
                              pm.parentid = ? ";
        } elseif ($arg == "assigned") {
            $query .= ", " . self::$acltable . " acl
                        WHERE p.id = pm.id AND
                              p.id = acl.permid AND
                              pm.parentid = ? ";
        }
        $query .=" AND p.type = ?";
        $query .=" ORDER BY p.name";

        $stmt = parent::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array(0,self::PRIVILEGES_PRIVILEGETYPE));

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
     * @param   none
     * @return  array of realm ids and names
     * @throws  none
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
     * @param   none
     * @return  array of module ids and names
     * @throws  none
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
                     //'display' => xarModGetDisplayableName($name),
                    'display' => ucfirst($result->getString(2))
                );
            }
        }
        return $allmodules;
    }

    /**
     * getcomponents: returns all the current components of a module.
     *
     * Returns an array of all the components that have been registered for a given module.
     * The components correspond to masks in the masks table. Each one can be used to
     * construct a privilege's xarSecurityCheck.
     * They are used to populate dropdowns in displays
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   string with module name
     * @return  array of component ids and names
     * @throws  none
     * @todo    this isn't really the right place for this function
    */
    public static function getcomponents($modid=null)
    {
        if (is_null($modid)) return array();
        if (!empty($modid)) {
            $modInfo = xarMod_GetBaseInfo(xarModGetNameFromID($modid));
            $modid = $modInfo['systemid'];
        }

        parent::initialize();
        $query = "SELECT DISTINCT component
                  FROM " . parent::$instancestable . "
                  WHERE module_id = ?
                  ORDER BY component";
        $stmt = parent::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($modid));
        $iter = $result->next();

        $components = array();
        if (empty($modid)){
            $components[] = array('id' => -2,
                               'name' => 'All');
        }
        elseif(count($result->fields) == 0) {
            $components[] = array('id' => -1,
                               'name' => 'All');
//          $components[] = array('id' => 0,
//                             'name' => 'None');
        }
        else {
            $components[] = array('id' => -1,
                               'name' => 'All');
//          $components[] = array('id' => 0,
//                             'name' => 'None');
            $ind = 2;
            while($iter) {
                $name = $result->getString(1);
                if (($name != 'All') && ($name != 'None')) {
                    $ind = $ind + 1;
                    $components[] = array(
                        'id'   => $name,
                        'name' => $name
                    );
                }
                $iter = $result->next();
            }
        }
        return $components;
    }

    /**
     * getinstances: returns all the current instances of a module.
     *
     * Returns an array of all the instances that have been defined for a given module.
     * The instances for each module are registered at initialization.
     * They are used to populate dropdowns in displays
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   string with module name
     * @return  array of instance ids and names for the module
     * @throws  none
     * @todo    this isn't really the right place for this function
    */
    public static function getinstances($module=null, $component)
    {
        if (is_null($module)) return array();
        $modid = 0;
        if (!empty($module)) $modid = xarMod::getID($module);

        parent::initialize();

        if ($component =="All") {
            $componentstring = "";
        }
        else {
            $componentstring = "AND ";
        }
        $query = "SELECT header, query, ddlimit
                  FROM " . parent::$instancestable ."
                  WHERE module_id = ? AND component = ?
                  ORDER BY component,id";
        $bindvars = array($modid,$component);

        $instances = array();
        $stmt = parent::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        while($result->next()) {
            list($header,$selection,$ddlimit) = $result->fields;

            // Check if an external instance wizard is requested, if so redirect using the URL in the 'query' part
            // This is indicated by the keyword 'external' in the 'header' of the instance definition
            if ($header == 'external') {
                return array('external' => 'yes',
                             'target'   => $selection);
            }

            // check if the query is there
            if ($selection =='') {
                $msg = xarML('A query is missing in component #(1) of module #(2)', $component, $module);
                // TODO: make it descendent from xarExceptions.
                throw new Exception($msg);
            }

            // We cant prepare this outside the loop as we have no idea what it is.
            $stmt1 = parent::$dbconn->prepareStatement($selection);
            $result1 = $stmt1->executeQuery();

            $dropdown = array();
            if (empty($modid)){
                $dropdown[] = array('id' => -2,'name' => '');
            }  elseif($result->EOF) { // FIXME: this never gets executed it think? it's outside the while condition.
                $dropdown[] = array('id' => -1,'name' => 'All');
    //          $dropdown[] = array('id' => 0, 'name' => 'None');
            }  else {
                $dropdown[] = array('id' => -1,'name' => 'All');
    //          $dropdown[] = array('id' => 0, 'name' => 'None');
            }
            while($result1->next()) {
                list($dropdownline) = $result1->fields;
                if (($dropdownline != 'All') && ($dropdownline != 'None')){
                    $dropdown[] = array('id' => $dropdownline, 'name' => $dropdownline);
                }
            }

            if (count($dropdown) > $ddlimit) {
                $type = "manual";
            } else {
                $type = "dropdown";
            }
            $instances[] = array('header' => $header,'dropdown' => $dropdown, 'type' => $type);
        }

        return $instances;
    }

    public static function getprivilegefast($id)
    {
        foreach(self::getprivileges() as $privilege){
            if ($privilege['id'] == $id) return $privilege;
        }
        return false;
    }

    /**
     * returnPrivilege: adds or modifies a privilege coming from an external wizard .
     *
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   strings with id, name, realm, module, component, instances and level
     * @return  mixed id if OK, void if not
    */
    public static function returnPrivilege($id,$name,$realm,$module,$component,$instances,$level)
    {
        $instance = "";
        foreach ($instances as $inst) { // mrb: why not use join()?
            $instance .= $inst . ":";
        }
        if ($instance =="") {
            $instance = "All";
        }
        else {
            $instance = substr($instance,0,strlen($instance)-1);
        }

        if($id==0) {
            $pargs = array('name' => $name,
                           'realm' => $realm,
                           'module' => $module,
                           'component' => $component,
                           'instance' => $instance,
                           'level' => $level,
                           'parentid' => 0
                           );
            sys::import('modules.privileges.class.privilege');
            $priv = new xarPrivilege($pargs);
            if ($priv->add()) {
                return $priv->getID();
            }
            return;
        }
        else {
            sys::import('modules.privileges.class.privileges');
            $priv = xarPrivileges::getPrivilege($id);
            $priv->setName($name);
            $priv->setRealm($realm);
            $priv->setModule($module);
            $priv->setModuleID($module);
            $priv->setComponent($component);
            $priv->setInstance($instance);
            $priv->setLevel($level);
            if ($priv->update()) {
                return $priv->getID();
            }
            return;
        }
    }

    /**
     * getPrivilege: gets a single privilege
     *
     * Retrieves a single privilege object from the Privileges repository
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   integer
     * @return  privilege object
     * @throws  none
     * @todo    none
    */
    public static function getPrivilege($id)
    {
        parent::initialize();
        static $stmt = null;  // Statement only needs to be prepared once.

        $cacheKey = 'Privilege.ByPid';
        if(xarCore::isCached($cacheKey,$id)) {
            return xarCore::getCached($cacheKey,$id);
        }
        // Need to get it
        $query = "SELECT p.id, p.name, r.name, p.module_id, m.name, p.component, p.instance, p.level, p.description
                  FROM " . parent::$privilegestable . " p LEFT JOIN ". parent::$realmstable ." r ON p.realmid = r.id
                  LEFT JOIN ". parent::$modulestable ." m ON p.module_id = m.id
                  WHERE type = ?";
        if(is_numeric($id)) $query .= " AND p.id = ?";
        else  $query .= " AND p.name = ?";

        if(!isset($stmt)) $stmt = parent::$dbconn->prepareStatement($query);
        //Execute the query, bail if an exception was thrown
        $result = $stmt->executeQuery(array(self::PRIVILEGES_PRIVILEGETYPE,$id),ResultSet::FETCHMODE_NUM);

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
            xarCore::setCached($cacheKey,$id,$priv);
            return $priv;
        } else {
            return null;
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
     * @return  privilege object
     * @throws  none
     * @todo    none
    */
    public static function findPrivilege($name)
    {
        static $stmt = null;

        parent::initialize();
        $query = "SELECT p.*, m.name FROM " . parent::$privilegestable . " p
        LEFT JOIN ". parent::$modulestable ." m ON p.module_id = m.id WHERE p.type = ? AND p.name = ?";
        if(!isset($stmt)) $stmt = parent::$dbconn->prepareStatement($query);

        //Execute the query, bail if an exception was thrown
        $result = $stmt->executeQuery(array(self::PRIVILEGES_PRIVILEGETYPE, $name));

        if ($result->first()) {
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
     * @return  privilege object
     * @throws  none
     * @todo    none
    */
    public static function findPrivilegesForModule($module)
    {
        static $stmt = null; // only prepare it once

        parent::initialize();
        $privileges = array();
        $query = "SELECT p.*, m.name FROM " . parent::$privilegestable . " p
        LEFT JOIN ". parent::$modulestable ." m ON p.module_id = m.id WHERE p.type = ? AND p.module_id = ?";
        //Execute the query, bail if an exception was thrown
        if(!isset($stmt)) $stmt = parent::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array(self::PRIVILEGES_PRIVILEGETYPE, $module));

        while ($result->next()) {
            list($id,$name,$realm,$module_id,$component,$instance,$level,$description,$module) = $result->fields;
            $pargs = array(
                'id'         => $id,
                'name'        => $name,
                'realm'       => $realm,
                'module_id'   => $module_id,
                'module'      => $module,
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
     * @throws  none
     * @todo    create exceptions for bad input
    */
    public static function makeMember($childname,$parentname)
    {
        $parent = self::findPrivilege($parentname);
        $child = self::findPrivilege($childname);
        return $parent->addMember($child);
    }

    /**
     * makeEntry: defines a top level entry of the privileges hierarchy
     *
     * Creates an entry in the privmembers table
     * This is a convenience class for module developers
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   string
     * @return  boolean
     * @throws  none
     * @todo    create exceptions for bad input
    */
    public static function makeEntry($rootname)
    {
        $priv = self::findPrivilege($rootname);
        $priv->makeEntry();
        return true;
    }
}

?>
