<?php
/**
 * @package modules
 * @subpackage privileges module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1098.html
 */

    /**
     * getinstances: returns all the current privilege instances of a module/component combination.
     *
     * Returns an array of all the instances that have been defined for a given module.
     * The instances for each module are registered at initialization.
     * They are used to populate dropdowns in displays
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
 * @param array    $args array of optional parameters<br/>
 *        string   $args['module']  module name<br/>
 *        string   $args['component']  component name
     * @return  array of instance ids and names for the module
    */

    function privileges_adminapi_getinstances(Array $args=array())
    {
        extract($args);
        
        if (is_null($module)) return array();
        try {
            $modid = xarMod::getID($module);
        } catch(Exception $e) {
            $modid = 0;
        }

        $dbconn = xarDB::getConn();
        $xartable =& xarDB::getTables();
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

            if (preg_match('/^xarMod::apiFunc/i',$selection)) {
                eval('$dropdown = ' . $this->func .';');
                if (!isset($dropdown)) $dropdown = array();
            } else {
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
            }
            $instances[] = array('header' => $header,'dropdown' => $dropdown, 'type' => $type);
        }

        return $instances;
    }
?>
