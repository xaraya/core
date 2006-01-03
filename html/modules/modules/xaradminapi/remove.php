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
 * @raise BAD_PARAM, NO_PERMISSION
 */
function modules_adminapi_remove($args)
{
    // Get arguments from argument array
    extract($args);

    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // Remove variables and module
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    // Get module information
    $modinfo = xarModGetInfo($regid);

    //TODO: Add check if there is any dependents
/*
    if (!xarModAPIFunc('modules','admin','verifydependents',array('regid'=>$regid))) {
        //TODO: Add description of the dependencies
        $msg = xarML('There are dependents to the module "#(1)" that weren\'t removed yet.', $modInfo['displayname']);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_DEPENDENCY', $msg);
        return;
    }
*/
    // If the files have been removed, the module will now also be removed from the db
    if ($modinfo['state'] == XARMOD_STATE_MISSING_FROM_UNINITIALISED ||
        $modinfo['state'] == XARMOD_STATE_MISSING_FROM_INACTIVE ||
        $modinfo['state'] == XARMOD_STATE_MISSING_FROM_ACTIVE ||
        $modinfo['state'] == XARMOD_STATE_MISSING_FROM_UPGRADED ) {

        // Delete any module variables that the module cleanup function might
        // have missed.
        // This needs to be done before the module entry is removed.
        xarModDelAllVars($modinfo['name']);

        $query = "DELETE FROM " . $tables['modules'] . " WHERE xar_regid = ?";
        $result =& $dbconn->Execute($query,array($modinfo['regid']));
        if (!$result) return;
        $query = "DELETE FROM " . $tables['system/module_states'] ." WHERE xar_regid = ?";
        $result =& $dbconn->Execute($query,array($modinfo['regid']));
        //NOTE: no use doing site/module_states now: see bug #1507 and xarDB.php for the details
        if (!$result) return;
    }
    else {
        // Module deletion function
        if (!xarModAPIFunc('modules',
                           'admin',
                           'executeinitfunction',
                           array('regid'    => $regid,
                                 'function' => 'delete'))) {
            //Raise an Exception
            return;
        }

        // Delete any module variables that the module cleanup function might
        // have missed.
        // This needs to be done before the module ntry is removed.
        // <mikespub> But *after* the delete() function of the module !
        xarModDelAllVars($modinfo['name']);

        // Update state of module
        $res = xarModAPIFunc('modules',
                            'admin',
                            'setstate',
                             array('regid' => $regid,
                                  'state' => XARMOD_STATE_UNINITIALISED));
    }

    // Delete any masks still around
    xarRemoveMasks($modinfo['name']);
    // Call any 'category' delete hooks assigned for that module
    // (notice we're using the module name as object id, and adding an
    // extra parameter telling xarModCallHooks for *which* module we're
    // calling hooks here)
    xarModCallHooks('module','remove',$modinfo['name'],'',$modinfo['name']);

    // Delete any hooks assigned for that module, or by that module
    $query = "DELETE FROM $tables[hooks] WHERE xar_smodule = ? OR xar_tmodule = ?";
    $bindvars = array($modinfo['name'],$modinfo['name']);
    $result =& $dbconn->Execute($query,$bindvars);
    if (!$result) {return;}

    //
    // Delete block details for this module.
    //

    // Get block types.
    $blocktypes = xarModAPIfunc(
        'blocks', 'user', 'getallblocktypes',
        array('module' => $modinfo['name'])
    );

    // Delete block types.
    if (is_array($blocktypes) && !empty($blocktypes)) {
        foreach($blocktypes as $blocktype) {
            $result = xarModAPIfunc(
                'blocks', 'admin', 'delete_type', $blocktype
            );
        }
    }

    // Check whether the module was the default module
    $defaultmod = xarConfigGetVar('Site.Core.DefaultModuleName');
    if ($modinfo['name'] == $defaultmod){
        xarConfigSetVar('Site.Core.DefaultModuleName', 'base');
    }

    return true;
}
?>
