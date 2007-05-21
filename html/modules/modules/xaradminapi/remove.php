<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Remove a module
 *
 * @author Xaraya Development Team
 * @param $args['regid'] the id of the module
 * @returns bool
 * @return true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function modules_adminapi_remove($args)
{
    // Get arguments from argument array
    extract($args);

    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // Remove variables and module
    $dbconn = xarDB::getConn();
    $tables = xarDB::getTables();

    // Get module information
    $modinfo = xarModGetInfo($regid);

    //TODO: Add check if there is any dependents
    // Make the whole thing atomic
    try {
        $dbconn->begin();

        // If the files have been removed, the module will now also be removed from the db
        if ($modinfo['state'] == XARMOD_STATE_MISSING_FROM_UNINITIALISED ||
            $modinfo['state'] == XARMOD_STATE_MISSING_FROM_INACTIVE ||
            $modinfo['state'] == XARMOD_STATE_MISSING_FROM_ACTIVE ||
            $modinfo['state'] == XARMOD_STATE_MISSING_FROM_UPGRADED ) {

            // Delete any module variables that the module cleanup function might
            // have missed.
            // This needs to be done before the module entry is removed.
            xarModDelAllVars($modinfo['name']);

            // Remove the module itself
            $query = "DELETE FROM $tables[modules] WHERE regid = ?";
            $dbconn->Execute($query,array($modinfo['regid']));
        } else {
            // Module deletion function
            xarModAPIFunc('modules', 'admin', 'executeinitfunction',
                          array('regid' => $regid, 'function' => 'delete'));

            // Delete any module variables that the module cleanup function might have missed.
            // This needs to be done before the module entry is removed.
            // <mikespub> But *after* the delete() function of the module !
            xarModDelAllVars($modinfo['name']);

            // Update state of module
            xarModAPIFunc('modules', 'admin', 'setstate',
                          array('regid' => $regid,'state' => XARMOD_STATE_UNINITIALISED));
        }

        // Delete any masks still around
        xarRemoveMasks($modinfo['name']);
        // Call any 'category' delete hooks assigned for that module
        // (notice we're using the module name as object id, and adding an
        // extra parameter telling xarModCallHooks for *which* module we're
        // calling hooks here)
        xarModCallHooks('module','remove',$modinfo['name'],'',$modinfo['name']);

        // Delete any hooks assigned for that module, or by that module
        $query = "DELETE FROM $tables[hooks] WHERE s_module_id = ? OR t_module_id = ?";
        $bindvars = array($modinfo['systemid'],$modinfo['systemid']);
        $dbconn->Execute($query,$bindvars);

        //
        // Delete block details for this module.
        //
        // Get block types.
        $blocktypes = xarModAPIfunc('blocks', 'user', 'getallblocktypes',
                                    array('module' => $modinfo['name']));

        // Delete block types.
        if (is_array($blocktypes) && !empty($blocktypes)) {
            foreach($blocktypes as $blocktype) {
                xarModAPIfunc('blocks', 'admin', 'delete_type', $blocktype);
            }
        }

        // Check whether the module was the default module
        $defaultmod = xarModVars::get('modules', 'defaultmodule');
        if ($modinfo['name'] == $defaultmod){
            xarModVars::set('modules', 'defaultmodule',xarMod::getID('base'));
        }
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }

    return true;
}
?>
