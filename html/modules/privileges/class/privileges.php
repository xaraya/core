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
     * @throws  none
     * @todo    none
    */
    static function defineInstance($module,$type,$instances,$propagate=0,$table2='',$childID='',$parentID='',$description='')
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
            $query = "SELECT instances.xar_iid
                      FROM   $iTable instances, $mTable mods
                      WHERE  instances.xar_modid = mods.xar_id AND
                             mods.xar_name = ? AND
                             instances.xar_component = ? AND
                             instances.xar_header = ?";
            $result = parent::$dbconn->execute($query, array($module, $type, $instance['header']));

            try {
                parent::$dbconn->begin();
                if (!$result->EOF) {
                    // Instance exists: update it.
                    list($iid) = $result->fields;
                    $query = "UPDATE $iTable
                          SET xar_query = ?, xar_limit = ?,
                              xar_propagate = ?, xar_instancetable2 = ?, xar_instancechildid = ?,
                              xar_instanceparentid = ?, xar_description = ?
                          WHERE xar_iid = ?";
                    $bindvars = array(
                                      $instance['query'], $instance['limit'],
                                      $propagate, $table2, $childID, $parentID,
                                      $description, $iid
                                      );
                } else {
                    $query = "INSERT INTO $iTable
                          ( xar_iid, xar_modid, xar_component, xar_header,
                            xar_query, xar_limit, xar_propagate,
                            xar_instancetable2, xar_instancechildid,
                            xar_instanceparentid, xar_description)
                          VALUES (?,?,?,?,?,?,?,?,?,?,?)";
                    $modInfo = xarMod_GetBaseInfo($module);
                    $modId = $modInfo['systemid'];
                    $bindvars = array(
                                      parent::$dbconn->genID(parent::$instancestable),
                                      $modId, $type, $instance['header'],
                                      $instance['query'], $instance['limit'],
                                      $propagate, $table2, $childID, $parentID,
                                      $description
                                      );
                }
                parent::$dbconn->Execute($query,$bindvars);
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
    static function removeInstances($module)
    {
        parent::initialize();
        try {
            parent::$dbconn->begin();
            $modInfo = xarMod_GetBaseInfo($module);
            $modId = $modInfo['systemid'];
            $query = "DELETE FROM " . parent::$instancestable . " WHERE xar_modid = ?";
            //Execute the query, bail if an exception was thrown
            parent::$dbconn->Execute($query,array($modId));
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
    static function register($name,$realm,$module,$component,$instance,$level,$description='')
    {
        parent::initialize();
        
        $realmid = null;
        if($realm != 'All') {
            $stmt = parent::$dbconn->prepareStatement('SELECT xar_rid FROM '.parent::$realmstable .' WHERE xar_name=?');
            $result = $stmt->executeQuery(array($realm),ResultSet::FETCHMODE_ASSOC);
            if($result->next()) $realmid = $result->getInt('xar_rid');
        }
        $query = "INSERT INTO " . parent::$privilegestable . " (
                    xar_pid, xar_name, xar_realmid, xar_module, xar_component,
                    xar_instance, xar_level, xar_description)
                  VALUES (?,?,?,?,?,?,?,?)";
        $bindvars = array(parent::$dbconn->genID(parent::$privilegestable),
                          $name, $realmid, $module, $component,
                          $instance, $level, $description);

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
    static function assign($privilegename,$rolename)
    {
        parent::initialize();
        // get the ID of the privilege to be assigned
        $privilege = self::findPrivilege($privilegename);
        $privid = $privilege->getID();

        // get the Roles class
        $roles = new xarRoles();

        // find the role for the assignation and get its ID
        $role = $roles->findRole($rolename);
        $roleid = $role->getID();

        // Add the assignation as an entry to the acl table
        $query = "INSERT INTO " . parent::$acltable . " VALUES (?,?)";
        $bindvars = array($roleid,$privid);
        parent::$dbconn->Execute($query,$bindvars);

        // empty the privset cache
        //        parent::$forgetprivsets();

        return true;
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
    static function getprivileges()
    {
        parent::initialize();
        static $allprivileges = array();

        if (empty($allprivileges)) {
            xarLogMessage('PRIV: getting all privs, once!');
            $query = "SELECT p.xar_pid, p.xar_name, r.xar_name,
                             p.xar_module, p.xar_component, p.xar_instance,
                             p.xar_level,  p.xar_description, pm.xar_parentid
                      FROM " . parent::$privmemberstable . " pm, ". 
                      parent::$privilegestable . " p LEFT JOIN ". parent::$realmstable . " r ON p.xar_realmid = r.xar_rid 
                      WHERE p.xar_pid = pm.xar_pid
                      ORDER BY p.xar_name";
            $stmt = parent::$dbconn->prepareStatement($query);
            // The fetchmode *needed* to be here, dunno why. Exception otherwise
            $result = $stmt->executeQuery($query,ResultSet::FETCHMODE_NUM);

            while($result->next()) {
                list($pid, $name, $realm, $module, $component, $instance, $level,
                        $description,$parentid) = $result->fields;
                $allprivileges[] = array('pid' => $pid,
                                   'name' => $name,
                                   'realm' => is_null($realm) ? 'All' : $realm,
                                   'module' => $module,
                                   'component' => $component,
                                   'instance' => $instance,
                                   'level' => $level,
                                   'description' => $description,
                                   'parentid' => $parentid);
            }
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
    static function gettoplevelprivileges($arg)
    {
        parent::initialize();
        // Base query
        $query = "SELECT p.xar_pid, p.xar_name,  r.xar_name,
                         p.xar_module,  p.xar_component, p.xar_instance,
                         p.xar_level, p.xar_description, pm.xar_parentid 
                  FROM " . parent::$privmemberstable . " pm, " . 
                           parent::$privilegestable  . " p LEFT JOIN " . parent::$realmstable . " r ON p.xar_realmid = r.xar_rid";
    
        if($arg == "all") {
             $query .= "WHERE p.xar_pid = pm.xar_pid AND
                              pm.xar_parentid = ? ";
        } elseif ($arg == "assigned") {
            $query .= ", " . self::$acltable . " acl
                        WHERE p.xar_pid = pm.xar_pid AND
                              p.xar_pid = acl.xar_permid AND
                              pm.xar_parentid = ? ";
        }
        $query .=" ORDER BY p.xar_name";
        $stmt = parent::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array(0));

        $privileges = array();
        $pids = array();
        while($result->next()) {
            list($pid, $name, $realm, $module, $component, $instance, $level,
                    $description,$parentid) = $result->fields;
            $thisone = $pid;
            if (!in_array($thisone,$pids)) {
                $pids[] = $thisone;
                $privileges[] = array(
                    'pid'         => $pid,
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
    static function getrealms()
    {
        parent::initialize();
        static $allreams = array(); // Get them once

        if (empty($allrealms)) {
            $query = "SELECT xar_rid, xar_name FROM " . parent::$realmstable;
            $stmt = parent::$dbconn->prepareStatement($query);
            $result = $stmt->executeQuery();

            // add some extra lines we want
            // $allrealms[] = array('rid' => -2,'name' => ' ');
            $allrealms[] = array('rid' => -1,'name' => 'All');
            // $allrealms[] = array('rid' => 0, 'name' => 'None');

            // add the realms from the database
            while($result->next()) {
                $allrealms[] = array(
                    'rid' => $result->getInt(1),
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
    static function getmodules()
    {
        parent::initialize();
        static $allmodules = array();

        if (empty($allmodules)) {
            $query = "SELECT modules.xar_id, modules.xar_name
                      FROM " . parent::$modulestable . " modules
                      WHERE modules.xar_state = ?
                      ORDER BY modules.xar_name";
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
    static function getcomponents($module)
    {
        parent::initialize();
        $modInfo = xarMod_GetBaseInfo($module);
        $modId = $modInfo['systemid'];
        $query = "SELECT DISTINCT xar_component
                  FROM " . parent::$instancestable . "
                  WHERE xar_modid= ?
                  ORDER BY xar_component";
        $stmt = parent::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($modId));

        $components = array();
        if ($module ==''){
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
            while($result->next()) {
                if (($name != 'All') && ($name != 'None')) {
                    $ind = $ind + 1;
                    $components[] = array(
                        'id'   => $result->getString(1),
                        'name' => $result->getString(1)
                    );
                }
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
    static function getinstances($module, $component)
    {
        parent::initialize();
        $modInfo = xarMod_GetBaseInfo($module);
        $modId = $modInfo['systemid'];

        if ($component =="All") {
            $componentstring = "";
        }
        else {
            $componentstring = "AND ";
        }
        $query = "SELECT xar_header, xar_query, xar_limit
                  FROM " . parent::$instancestable ."
                  WHERE xar_modid= ? AND xar_component= ?
                  ORDER BY xar_component,xar_iid";
        $bindvars = array($modId,$component);

        $instances = array();
        $stmt = parent::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        while($result->next()) {
            list($header,$selection,$limit) = $result->fields;

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

            $stmt1 = parent::$dbconn->prepareStatement($selection);
            $result1 = $stmt1->executeQuery();

            $dropdown = array();
            if ($module ==''){
                $dropdown[] = array('id' => -2,'name' => '');
            }  elseif($result->EOF) {
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

            if (count($dropdown) > $limit) {
                $type = "manual";
            } else {
                $type = "dropdown";
            }
            $instances[] = array('header' => $header,'dropdown' => $dropdown, 'type' => $type);
        }

        return $instances;
    }

    static function getprivilegefast($pid)
    {
        foreach(self::getprivileges() as $privilege){
            if ($privilege['pid'] == $pid) return $privilege;
        }
        return false;
    }

    static function getChildren($pid)
    {
        $subprivileges = array();
        $ind = 1;
        foreach(self::getprivileges() as $subprivilege){
            if ($subprivilege['parentid'] == $pid) {
                $subprivileges[$ind++] = $subprivilege;
            }
        }
        return $subprivileges;
    }

    /**
     * returnPrivilege: adds or modifies a privilege coming from an external wizard .
     *
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   strings with pid, name, realm, module, component, instances and level
     * @return  mixed pid if OK, void if not
    */
    static function returnPrivilege($pid,$name,$realm,$module,$component,$instances,$level)
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

        if($pid==0) {
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
            $privs = new xarPrivileges();
            $priv = $privs->getPrivilege($pid);
            $priv->setName($name);
            $priv->setRealm($realm);
            $priv->setModule($module);
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
    static function getPrivilege($pid)
    {
        parent::initialize();
        static $stmt = null;  // Statement only needs to be prepared once.

        $cacheKey = 'Privilege.ByPid';
        if(xarVarIsCached($cacheKey,$pid)) {
            return xarVarGetCached($cacheKey,$pid);
        }
        // Need to get it
        $query = "SELECT p.xar_pid, p.xar_name, r.xar_name, p.xar_module, p.xar_component, p.xar_instance, p.xar_level, p.xar_description 
                  FROM " . parent::$privilegestable . " p LEFT JOIN ". parent::$realmstable ." r ON p.xar_realmid = r.xar_rid 
                  WHERE xar_pid = ?";
        if(!isset($stmt)) $stmt = parent::$dbconn->prepareStatement($query);
        //Execute the query, bail if an exception was thrown
        $result = $stmt->executeQuery(array($pid),ResultSet::FETCHMODE_NUM);

        if ($result->next()) {
            list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
            $pargs = array('pid'=>$pid,
                           'name'=>$name,
                           'realm'=> is_null($realm) ? 'All' : $realm,
                           'module'=>$module,
                           'component'=>$component,
                           'instance'=>$instance,
                           'level'=>$level,
                           'description'=>$description,
                           'parentid'=>0);

            sys::import('modules.privileges.class.privilege');
            $priv = new xarPrivilege($pargs);
            xarVarSetCached($cacheKey,$pid,$priv);
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
    static function findPrivilege($name)
    {
        parent::initialize();
        $query = "SELECT * FROM " . parent::$privilegestable . " WHERE xar_name = ?";
        //Execute the query, bail if an exception was thrown
        $result = parent::$dbconn->Execute($query,array($name));

        if (!$result->EOF) {
            list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
            $pargs = array('pid'=>$pid,
                           'name'=>$name,
                           'realm'=>$realm,
                           'module'=>$module,
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
    static function findPrivilegesForModule($module)
    {
        static $stmt = null; // only prepare it once
        
        parent::initialize();
        $privileges = array();
        $query = "SELECT * FROM " . parent::$privilegestable . " WHERE xar_module = ?";
        //Execute the query, bail if an exception was thrown
        if(!isset($stmt)) $stmt = parent::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($module));

        while ($result->next()) {
            list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
            $pargs = array(
                'pid'         => $pid,
                'name'        => $name,
                'realm'       => $realm,
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
    static function makeMember($childname,$parentname)
    {
        parent::initialize();
        // get the data for the parent object
        $query = "SELECT *
                  FROM " . parent::$privilegestable . " WHERE xar_name = ?";
        //Execute the query, bail if an exception was thrown
        $result = parent::$dbconn->Execute($query,array($parentname));


// create the parent object
        list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
        $pargs = array('pid'=>$pid,
                        'name'=>$name,
                        'realm'=>$realm,
                        'module'=>$module,
                        'component'=>$component,
                        'instance'=>$instance,
                        'level'=>$level,
                        'description'=>$description,
                        'parentid'=>0);
        $parent =  new xarPrivilege($pargs);

// get the data for the child object
        $query = "SELECT * FROM " . parent::$privilegestable . " WHERE xar_name = ?";
        //Execute the query, bail if an exception was thrown
        $result = parent::$dbconn->Execute($query,array($childname));


// create the child object
        list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
        $pargs = array('pid'=>$pid,
                        'name'=>$name,
                        'realm'=>$realm,
                        'module'=>$module,
                        'component'=>$component,
                        'instance'=>$instance,
                        'level'=>$level,
                        'description'=>$description,
                        'parentid'=>0);
        $child =  new xarPrivilege($pargs);

// done
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
    static function makeEntry($rootname)
    {
        $priv = self::findPrivilege($rootname);
        $priv->makeEntry();
        return true;
    }
}

?>
