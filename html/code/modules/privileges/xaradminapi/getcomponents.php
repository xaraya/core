<?php
/**
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 */

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
 * @param array    $args array of optional parameters<br/>
 *        string   $args['module']  module name
     * @return  array of component ids and names
    */

    function privileges_adminapi_getcomponents(Array $args=array())
    {
        extract($args);
        
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
        $xartable =& xarDB::getTables();
        $query = "SELECT DISTINCT component
                  FROM " . $xartable['security_instances'] . "
                  WHERE module_id = ?
                  ORDER BY component";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($modid));
        $iter = $result->next();

        $components = array();
        if(count($result->fields) == 0) {
            $components[] = array('id' => 'All', 'name' => 'All');
//          $components[] = array('id' => 0, 'name' => 'None');
        } else {
            $components[] = array('id' => 'All', 'name' => 'All');
//          $components[] = array('id' => 0,'name' => 'None');
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