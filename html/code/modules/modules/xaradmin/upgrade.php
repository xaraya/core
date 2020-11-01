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
 * Upgrade a module
 *
 * Loads module admin API and calls the upgrade function
 * to actually perform the upgrade, then redrects to
 * the list function and with a status message and returns
 * true.
 *
 * @author Xaraya Development Team
 * @param id the module id to upgrade
 * @return boolean true on success, false on failure
 */
function modules_admin_upgrade()
{
    // Security
    if (!xarSecurity::check('AdminModules')) return; 
    
    // Security and sanity checks
    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    if (!xarVar::fetch('id', 'int:1:', $id, 0, xarVar::NOT_REQUIRED)) {return;}
    if (empty($id)) return xarResponse::notFound();
    if (!xarVar::fetch('return_url', 'pre:trim:str:1:',
        $return_url, '', xarVar::NOT_REQUIRED)) return;
        
    $success = true;

    // See if we have lost any modules since last generation
    sys::import('modules.modules.class.installer');
    $installer = Installer::getInstance();    
    if (!$installer->checkformissing()) {
        return;
    }

    // TODO: give the user the opportunity to upgrade the dependancies automatically.
    try {
        $installer->verifydependency($id);
        $minfo=xarMod::getInfo($id);
        //Bail if we've lost our module
        if ($minfo['state'] != xarMod::STATE_MISSING_FROM_UPGRADED) {
            // Upgrade module
            $upgraded = xarMod::apiFunc('modules', 'admin', 'upgrade',array('regid' => $id));
        }
    } catch (Exception $e) {
        // TODO: gradually build up the handling here, for now, bail early.
        throw $e;
    }

    // set the target location (anchor) to go to within the page
    $target=$minfo['name'];
    if (empty($return_url))
        $return_url = xarController::URL('modules', 'admin', 'list', array('state' => 0), NULL, $target);
    // Hmmm, I wonder if the target adding is considered a hack
    // it certainly depends on the implementation of xarModUrl
    //    xarController::redirect(xarController::URL('modules', 'admin', "list#$target"));
    xarController::redirect($return_url);

    return true;
}

?>
