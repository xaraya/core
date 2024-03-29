<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
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
 * @param int id the module id to deactivate
 * @return boolean|string|void true on success, false on failure
 */
function modules_admin_deactivate ()
{
    // Security
    if (!xarSecurity::check('AdminModules')) return; 
    
    // Security and sanity checks
    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    if (!xarVar::fetch('id', 'int:1:', $id, 0, xarVar::NOT_REQUIRED)) return;
    if (empty($id)) return xarResponse::notFound();
    if (!xarVar::fetch('return_url', 'pre:trim:str:1:',
        $return_url, '', xarVar::NOT_REQUIRED)) return;
        
    //Checking if the user has already passed thru the GUI:
    xarVar::fetch('command', 'checkbox', $command, false, xarVar::NOT_REQUIRED);

    // set the target location (anchor) to go to within the page
    $minfo=xarMod::getInfo($id);
    $target=$minfo['name'];
    if (empty($return_url))
        $return_url = xarController::URL('modules', 'admin', 'list', array('state' => 0), NULL, $target);

    sys::import('modules.modules.class.installer');
    $installer = Installer::getInstance();    

    // If we haven't been to the deps GUI, check that first
    if (!$command) {
        // First check for the modules depending on this one
        $dependents = $installer->getalldependents($id);
        if(count($dependents['active']) > 1) {
            //Let's make a nice GUI to show the user the options
            $data = array();
            $data['id'] = $id;
            //They come in 2 arrays: active, initialised
            //Both have $name => $modInfo under them foreach
            $data['authid']       = xarSec::genAuthKey();
            $data['dependencies'] = $dependents;
            return $data;
        } else {
            // No dependents, we can deactivate the module
            if(!xarMod::apiFunc('modules','admin','deactivate',array('regid' => $id)))  return;
            xarController::redirect($return_url);               
        }
    }

    // See if we have lost any modules since last generation
    if (!$installer->checkformissing()) { return; }

    //Bail if we've lost our module
    if ($minfo['state'] != xarMod::STATE_MISSING_FROM_ACTIVE) {
        //Deactivate with dependents, first dependents
        //then the module itself
        if (!$installer->deactivatewithdependents($id)) {
            //Call exception
            return;
        } // Else
    }

    // Hmmm, I wonder if the target adding is considered a hack
    // it certainly depends on the implementation of xarController::URL
    xarController::redirect($return_url);

    return true;
}
