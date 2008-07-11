<?php
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
    */

    function privileges_adminapi_getcomponents($args)
    {
        extract($args);
        
//        if (is_null($modid)) return array();
        if (empty($modid)){
            $components[] = array('id' => -2,
                               'name' => 'All');
        } else {
            $module = xarModGetNameFromID($modid);

            // Do we have the components in a file?
            try {
                sys::import('modules.' . $module . '.security');
                return getcomponents();
            } catch(Exception $e) {}

            $modid = xarMod::getID($module);
        }

        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();
        $query = "SELECT DISTINCT component
                  FROM " . $xartable['security_instances'] . "
                  WHERE module_id = ?
                  ORDER BY component";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($modid));
        $iter = $result->next();

        $components = array();
        if(count($result->fields) == 0) {
            $components[] = array('id' => -1,
                               'name' => 'All');
//          $components[] = array('id' => 0,
//                             'name' => 'None');
        } else {
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
?>
