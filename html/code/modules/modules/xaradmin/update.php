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
 * Update a module
 *
 * @author Xaraya Development Team
 * @param id the module's registered id
 * @param newdisplayname the new display name
 * @param newdescription the new description
 * @return mixed true on success, error message on failure
 */
function modules_admin_update()
{
    // Security
    if (!xarSecurity::check('EditModules')) return; 
    
    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // Get parameters
    xarVar::fetch('id','id',$regId);
    // CHECKME: what's this?
    xarVar::fetch('newdisplayname','str::',$newDisplayName); 

    if (!xarSec::confirmAuthKey()) {
        //return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // update hooks...
    if (!xarVar::fetch('observers', 'array', $observers, array(), xarVar::NOT_REQUIRED)) return;
    
    if (!xarMod::apiFunc('modules', 'admin', 'update',
        array(
            'regid' => $regId,
            'displayname' => $newDisplayName,
            'observers' => $observers,
        ))) return;

    xarVar::fetch('return_url', 'isset', $return_url, NULL, xarVar::DONT_SET);
    if (!empty($return_url)) {
        xarController::redirect($return_url);
    } else {
        xarController::redirect(xarController::URL('modules', 'admin', 'modify', array('id' => $regId)));
    }
    
    return true;
}

?>
