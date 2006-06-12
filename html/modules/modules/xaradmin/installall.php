<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Installs a module
 *
 * Loads module admin API and calls the initialise
 * function to actually perform the initialisation,
 * then redirects to the list function with a
 * status message and returns true.
 * <andyv implementation of JC's request> attempt to activate module immediately after it's inited
 *
 * @author Xaraya Development Team
 * @param id the module id to initialise
 * @returns
 * @return
 */
function modules_admin_installall()
{
    // Security and sanity checks
    //Testing it directly for now... Insert this back when it is put into the template
//    if (!xarSecConfirmAuthKey()) return;

    //This is a very lenghty process
   @set_time_limit(600);

    // Get all modules in DB
    $dbModules = xarModAPIFunc('modules','admin','getdbmodules');
    if (!isset($dbModules)) return;

    foreach ($dbModules as $name => $info) {
        //Jump if already installed
        if ($info['state'] == XARMOD_STATE_INSTALLED) continue;
        $dependencies = xarModAPIFunc('modules','admin','getalldependencies',array('regid'=>$info['regid']));
        //If this cannot be installed, jump it
        if (count($dependencies['unsatisfiable']) > 0) {
            continue;
        } else {
               if (xarModAPIFunc('modules','admin','installwithdependencies',array('regid'=>$info['regid']))) {
                foreach ($dependencies['satisfiable'] as $key => $modInfo) {
                    $dbModules[$modInfo['name']]['state'] = XARMOD_STATE_INSTALLED;
                }
            }
        }
    }

    xarResponseRedirect(xarModURL('modules', 'admin', 'list', array('state' => 0), NULL));

    return true;
}

?>
