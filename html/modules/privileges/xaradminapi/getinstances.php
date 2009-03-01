<?php

    /**
     * getinstances: returns all the current privilege instances of a module/component combination.
     *
     * Returns an array of all the instances that have been defined for a given module.
     * The instances for each module are registered at initialization.
     * They are used to populate dropdowns in displays
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   string with module name
     * @param   string with component name
     * @return  array of instance ids and names for the module
     * @throws  none
    */

    function privileges_adminapi_getinstances($args)
    {
        extract($args);
        
        if (is_null($modid)) return array();
        if (!empty($modid)) $modid = xarMod::getID(xarModGetNameFromID($modid));

//        if ($component =="All") $componentstring = "";
//        else $componentstring = "AND ";

        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();
        $query = "SELECT header, query, ddlimit
                  FROM " . $xartable['security_instances'] ."
                  WHERE module_id = ? AND component = ?
                  ORDER BY component,id";
        $bindvars = array($modid,$component);

        $instances = array();
        $stmt = $dbconn->prepareStatement($query);
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
                $msg = xarML('A query is missing in component #(1) of module #(2)', $component, xarModGetNameFromID($modid));
                // TODO: make it descendent from xarExceptions.
                throw new Exception($msg);
            }

            // We cant prepare this outside the loop as we have no idea what it is.
            $stmt1 = $dbconn->prepareStatement($selection);
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
?>
