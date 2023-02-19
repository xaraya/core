<?php
/**
 *
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */

/**
 * Activate a module
 *
 * @author Xaraya Development Team
 * Loads module admin API and calls the activate
 * function to actually perform the activation,
 * then redirects to the list function with a
 * status message and returns true.
 *
 * @param int id the module id to activate
 * @return boolean|string|void true on success, false on failure
 */
function modules_admin_activate()
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
        
    // Activate
    $activated = xarMod::apiFunc('modules',
                              'admin',
                              'activate',
                              array('regid' => $id));

    //throw back
    if (!isset($activated)) return;
    $minfo=xarMod::getInfo($id);
    // set the target location (anchor) to go to within the page
    $target=$minfo['name'];
    if (empty($return_url))
        $return_url = xarController::URL('modules', 'admin', 'list', array('state' => 0), NULL, $target);
        
    xarController::redirect($return_url);
    return true;
}
