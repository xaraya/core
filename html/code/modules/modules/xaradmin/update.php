<?php
/**
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
    // Get parameters
    xarVarFetch('id','id',$regId);
    // CHECKME: what's this?
    xarVarFetch('newdisplayname','str::',$newDisplayName); 

    if (!xarSecConfirmAuthKey()) {
        //return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // update hooks...
    if (!xarVarFetch('observers', 'array', $observers, array(), XARVAR_NOT_REQUIRED)) return;
    
    if (!xarMod::apiFunc('modules', 'admin', 'update',
        array(
            'regid' => $regId,
            'displayname' => $newDisplayName,
            'observers' => $observers,
        ))) return;

    xarVarFetch('return_url', 'isset', $return_url, NULL, XARVAR_DONT_SET);
    if (!empty($return_url)) {
        xarController::redirect($return_url);
    } else {
        xarController::redirect(xarModURL('modules', 'admin', 'modify', array('id' => $regId)));
    }
    
    return true;
}

?>
