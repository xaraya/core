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
 * Remove a module
 *
 * Loads module admin API and calls the remove function
 * to actually perform the removal, then redirects to
 * the list function with a status message and retursn true.
 *
 * @author Xaraya Development Team
 * @access public
 * @param  id the module id
 * @return mixed true on success
 */

// Remove/Deactivate/Install GUI functions are basically copied and pasted versions...
// Refactor later on
function modules_admin_remove ()
{
    // Security
    if (!xarSecurityCheck('AdminModules')) return; 
    
     // Security and sanity checks
    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    if (!xarVarFetch('id', 'int:1:', $id, 0, XARVAR_NOT_REQUIRED)) return;
    if (empty($id)) return xarResponse::notFound();
    if (!xarVarFetch('return_url', 'pre:trim:str:1:',
        $return_url, '', XARVAR_NOT_REQUIRED)) return;
        
    //Checking if the user has already passed thru the GUI:
    xarVarFetch('command', 'checkbox', $command, false, XARVAR_NOT_REQUIRED);

    $minfo=xarMod::getInfo($id);

    // set the target location (anchor) to go to within the page
    $target=$minfo['name'];
    if (empty($return_url))
        $return_url = xarModURL('modules', 'admin', 'list', array('state' => 0), NULL, $target);

    sys::import('modules.modules.class.installer');
    $installer = Installer::getInstance();    
    if(!$command) {
        // not been thru gui yet, first check the modules dependencies
        $dependents = $installer->getalldependents($id);
        if (!(count($dependents['active']) > 0 || count($dependents['initialised']) > 1 )) {
            //No dependents, just remove the module
            if(!xarMod::apiFunc('modules','admin','remove',array('regid' => $id)))  return;
            // Clear the property cache
            PropertyRegistration::importPropertyTypes(true);
            xarController::redirect($return_url);
        } else {
            // There are dependents, let's build a GUI
            $data                 = array();
            $data['id']           = $id;
            $data['authid']       = xarSecGenAuthKey();
            $data['dependencies'] = $dependents;
            $data['return_url']   = $return_url;
            return $data;
        }
    }

    // User has seen the GUI
    // Removes with dependents, first remove the necessary dependents then the module itself
    if (!$installer->removewithdependents($id)) {
        //Call exception
        xarLog::message('Missing module since last generation!', xarLog::LEVEL_WARNING);
        return;
    } // Else

    // Clear the property cache
    PropertyRegistration::importPropertyTypes(true);

    // Hmmm, I wonder if the target adding is considered a hack
    // it certainly depends on the implementation of xarModUrl
    //    xarController::redirect(xarModURL('modules', 'admin', "list#$target"));
    xarController::redirect($return_url);
    // Never reached
    return true;
}

?>
