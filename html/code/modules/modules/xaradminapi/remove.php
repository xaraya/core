<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Remove a module
 *
 * @author Xaraya Development Team
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['regid'] the id of the module
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function modules_adminapi_remove(Array $args=array())
{
    // Get arguments from argument array
    extract($args);

    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // Remove variables and module
    $dbconn = xarDB::getConn();
    $tables = xarDB::getTables();

    // Get module information
    $modinfo = xarMod::getInfo($regid);

    //TODO: Add check if there is any dependents
    // Make the whole thing atomic
    try {
        $dbconn->begin();

        // If the files have been removed, the module will now also be removed from the db
        if ($modinfo['state'] == XARMOD_STATE_MISSING_FROM_UNINITIALISED ||
            $modinfo['state'] == XARMOD_STATE_MISSING_FROM_INACTIVE ||
            $modinfo['state'] == XARMOD_STATE_MISSING_FROM_ACTIVE ||
            $modinfo['state'] == XARMOD_STATE_MISSING_FROM_UPGRADED ) {

            // All cleanup needs to happen before a module entry is removed
            xarEvents::notify('ModRemove', $modinfo['name']);
            // this is now handled by the modules module ModRemove event observer
            //xarModVars::delete_all($modinfo['name']);

            // Remove the module itself
            $query = "DELETE FROM $tables[modules] WHERE regid = ?";
            $dbconn->Execute($query,array($modinfo['regid']));
        } else {
            // Module deletion function
            xarMod::apiFunc('modules', 'admin', 'executeinitfunction',
                          array('regid' => $regid, 'function' => 'delete'));

            // All cleanup needs to happen before a module entry is removed
            xarEvents::notify('ModRemove', $modinfo['name']);
            // this is now handled by the modules module ModRemove event observer
            //xarModVars::delete_all($modinfo['name']);

            // Update state of module
            xarMod::apiFunc('modules', 'admin', 'setstate',
                          array('regid' => $regid,'state' => XARMOD_STATE_UNINITIALISED));
        }

        // Delete any masks still around
        // this is now handled by the modules module ModRemove event observer
        // xarRemoveMasks($modinfo['name']);
        // this is now handled by the modules module ModRemove event observer
        // xarModCallHooks('module','remove',$modinfo['name'],'',$modinfo['name']);

        //
        // Delete block details for this module.
        //
        // Get block types.
        // this is now handled by the modules module ModRemove event observer
        /*
        $blocktypes = xarMod::apiFunc('blocks', 'user', 'getallblocktypes',
                                    array('module' => $modinfo['name']));

        // Delete block types.
        if (is_array($blocktypes) && !empty($blocktypes)) {
            foreach($blocktypes as $blocktype) {
                xarMod::apiFunc('blocks', 'admin', 'delete_type', $blocktype);
            }
        }
        */
        // this is now handled by the modules module ModRemove event observer
        /*
        $defaultmod = xarModVars::get('modules', 'defaultmodule');
        if ($modinfo['name'] == $defaultmod) {
            xarModVars::set('modules', 'defaultmodule','base');
        }
        */
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }

    return true;
}
?>
