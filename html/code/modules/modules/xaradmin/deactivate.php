<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Deactivate a module
 *
 * @author Xaraya Development Team
 * Loads module admin API and calls the setstate
 * function to actually perfrom the deactivation,
 * then redirects to the list function with a status
 * message and returns true.
 *
 * @access public
 * @param id the mdoule id to deactivate
 * @return boolean true on success, false on failure
 */
function modules_admin_deactivate ()
{
    // Security
    if (!xarSecurityCheck('AdminModules')) return; 
    
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    if (!xarVarFetch('id', 'int:1:', $id, 0, XARVAR_NOT_REQUIRED)) return;
    if (empty($id)) return xarResponse::notFound();

    //Checking if the user has already passed thru the GUI:
    xarVarFetch('command', 'checkbox', $command, false, XARVAR_NOT_REQUIRED);

    // set the target location (anchor) to go to within the page
    $minfo=xarMod::getInfo($id);
    $target=$minfo['name'];

    sys::import('modules.modules.class.installer');
    $installer = Installer::getInstance();    

    // If we haven't been to the deps GUI, check that first
    if (!$command) {
        //First check the modules dependencies
        $dependents = $installer->getalldependents($id);
        if(count($dependents['active']) > 1) {
            //Let's make a nice GUI to show the user the options
            $data = array();
            $data['id'] = $id;
            //They come in 2 arrays: active, initialised
            //Both have $name => $modInfo under them foreach
            $data['authid']       = xarSecGenAuthKey();
            $data['dependencies'] = $dependents;
            return $data;
        } else {
            // No dependents, we can deactivate the module
            if(!xarMod::apiFunc('modules','admin','deactivate',array('regid' => $id)))  return;
            xarController::redirect(xarModURL('modules', 'admin', 'list', array('state' => 0), NULL, $target));               
        }
    }

    // See if we have lost any modules since last generation
    if (!$installer->checkformissing()) { return; }

    //Bail if we've lost our module
    if ($minfo['state'] != XARMOD_STATE_MISSING_FROM_ACTIVE) {
        //Deactivate with dependents, first dependents
        //then the module itself
        if (!$installer->deactivatewithdependents($id)) {
            //Call exception
            return;
        } // Else
    }

    // Hmmm, I wonder if the target adding is considered a hack
    // it certainly depends on the implementation of xarModUrl
    xarController::redirect(xarModURL('modules', 'admin', 'list', array('state' => 0), NULL, $target));

    return true;
}

?>
