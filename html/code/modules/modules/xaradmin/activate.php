<?php
/**
 *
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
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
 * @param id the module id to activate
 * @return boolean true on success, false on failure
 */
function modules_admin_activate()
{
    // Security
    if (!xarSecurityCheck('AdminModules')) return; 
    
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    if (!xarVarFetch('id', 'int:1:', $id, 0, XARVAR_NOT_REQUIRED)) return;
    if (empty($id)) return xarResponse::notFound();

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

    xarController::redirect(xarModURL('modules', 'admin', 'list', array('state' => 0), NULL, $target));
    return true;
}

?>
