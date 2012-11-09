<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */
function modules_adminapi_countitems(Array $args=array())
{
    extract($args);
    
    if (!isset($state))
        $state = XARMOD_STATE_ACTIVE;
    
    if (!isset($include_core))
        $include_core = true;
        
    // Determine the tables we are going to use
    $dbconn = xarDB::getConn();
    $tables = xarDB::getTables();
    $modules_table = $tables['modules'];
    
    $select = array();
    $where = array();
    $bindvars = array();
 
    if (!empty($regid)) {
        $where[] = 'mods.regid = ?';
        $bindvars[] = $regid;
    }    
    
    if (!empty($name)) {
        if (is_array($name)) {
            $where[] = 'mods.name IN (' . implode(',', array_fill(0, count($name), '?')) . ')';
            $bindvars = array_merge($bindvars, $name);
        } else {             
            $where[] = 'mods.name = ?';
            $bindvars[] = $name;
        }
    }
    
    if (!empty($systemid)) {
        $where[] = 'mods.id = ?';
        $bindvars[] = $systemid;
    }  

    if ($state != XARMOD_STATE_ANY) {
        if ($state != XARMOD_STATE_INSTALLED) {
            $where[] = 'mods.state = ?';
            $bindvars[] = $state;
        } else {
            $where[] = 'mods.state != ? AND mods.state < ? AND mods.state != ?';
            $bindvars[] = XARMOD_STATE_UNINITIALISED;
            $bindvars[] = XARMOD_STATE_MISSING_FROM_INACTIVE;
            $bindvars[] = XARMOD_STATE_MISSING_FROM_UNINITIALISED;
        }    
    }
    
    if (!empty($modclass)) {
        $where[] = 'mods.class = ?';
        $bindvars[] = $modclass;
    }
    
    if (!empty($category)) {
        $where[] = 'mods.category = ?';
        $bindvars[] = $category;
    }
    
    if (!$include_core) {
        $coremods = array('base','roles','privileges','blocks','themes','authsystem','mail','dynamicdata','installer','modules','categories');
        $where[] = 'mods.name NOT IN (' . implode(',', array_fill(0, count($coremods), '?')) . ')';
        $bindvars = array_merge($bindvars, $coremods);        
    }

    if (isset($user_capable)) {
        $where[] = 'mods.user_capable = ?';
        $bindvars[] = (bool) $user_capable;
    }

    if (isset($admin_capable)) {
        $where[] = 'mods.admin_capable = ?';
        $bindvars[] = (bool) $admin_capable;
    }  
    
    
    // build query
    $query = "SELECT COUNT(mods.id)"; 
    $query .= " FROM $modules_table mods";
    if (!empty($where))
        $query .= ' WHERE ' . join(' AND ', $where);    
    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;    
    list($count) = $result->fields;

    $result->Close();
    
    return $count;
}
?>