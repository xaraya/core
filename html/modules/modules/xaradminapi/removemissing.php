<?php
/**
 * Remove a module when the files are missing
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Remove a module when the files are missing
 *
 * @author Xaraya Development Team
 * @param $args['regid'] the id of the module
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function modules_adminapi_removemissing($args)
{
    // Get arguments from argument array
    extract($args);

// TODO (random) This whole exercise is on hold because w have no way of knowing which
// tables actually belong to the module being removed, and so the cleanup is incomplete
// For now just remove the entry in the modules and modules states tables

//    if (!xarVarFetch('remove', 'str', $remove, NULL, XARVAR_NOT_REQUIRED)) return;
    // Get module information
//    $modinfo = xarModGetInfo($regid);
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $query = "DELETE FROM " . $tables['modules'] . " WHERE xar_regid = ?";
    $result = $dbconn->Execute($query,array($regid));
    // This next entry probably already gone, but lets be sure
    $query = "DELETE FROM " . $tables['system/module_states'] . " WHERE xar_regid = ?";
    $result = $dbconn->Execute($query,array($regid));

/*    if(isset($remove)) {

        // Delete any module variables that the module cleanup function might
        // have missed.
        // This needs to be done before the module entry is removed.
        xarModDelAllVars($modinfo['name']);

        // Delete any masks still around
        xarRemoveMasks($modinfo['name']);
        // Call any 'category' delete hooks assigned for that module
        // (notice we're using the module name as object id, and adding an
        // extra parameter telling xarModCallHooks for *which* module we're
        // calling hooks here)
        xarModCallHooks('module','remove',$modinfo['name'],'',$modinfo['name']);

        // Delete any hooks assigned for that module, or by that module
        $query = "DELETE FROM $tables[hooks]
                  WHERE xar_smodule = ? OR xar_tmodule = ?";
        $result = $dbconn->Execute($query,array($modinfo['name'],$modinfo['name']));
        if (!$result) return;

        // Collect the block types and remove them
        $query = "SELECT xar_id
                  FROM $tables[block_types]
                  WHERE xar_module = ?";
        $result = $dbconn->Execute($query,array($modinfo['name']));
        if (!$result) return;
        while (!$result->EOF) {
            list($typeid) = $result->fields;
            $query = "DELETE FROM $tables[block_instances]
                      WHERE xar_type_id = ?";
            $result1 =& $dbconn->Execute($query,array($typeid));
            if (!$result1) return;
            $result->MoveNext();
        }
        $query = "DELETE FROM $tables[block_types]
                  WHERE xar_module = ?";
        $result = $dbconn->Execute($query,array($modinfo['name']));
        if (!$result) return;

        foreach($tablestodrop as $tabletodrop) {
            xarDBLoadTableMaintenanceAPI();
            // Delete tables
            // Generate the SQL to drop the table using the API
            echo $table . " " . $modinfo['name']."<br />";
            $query = xarDBDropTable($tabletodrop);
            if (empty($query)) return;
        }
        return true;
    }
    else {
        // We need to identify the data tables
        // we make an educated guess and ask the admin to confirm
        $tablestodrop = array();
        $tablestable = xarDBGetSiteTablePrefix() . '_tables';
        $query = "SELECT DISTINCT xar_table
                  FROM " .  $tablestable;
        $result = $dbconn->Execute($query);
        if (!$result) return;
        while (!$result->EOF) {
            list($table) = $result->fields;
            if(strstr($table,'_'.$modinfo['name'])) $tablestodrop[] = $table;
            $result->MoveNext();
        }
        $data['tablestodrop'] = $tablestodrop;
        return xarTplModule('modules','adminapi', 'removemissing',$data);
    }
*/
    return true;
}

?>
