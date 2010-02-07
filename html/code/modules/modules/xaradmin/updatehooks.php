<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Update hooks by hook module
 *
 * @param none
 *
 * @author Xaraya Development Team
 */
function modules_admin_updatehooks()
{
// Security Check
    if(!xarSecurityCheck('AdminModules')) {return;}

    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        
    // Curhook contains module name
    if (!xarVarFetch('curhook', 'str:1:', $curhook)) {return;}

    $regId = xarMod::getRegID($curhook);
    if (!isset($curhook) || !isset($regId)) {
        $msg = xarML('Invalid hook');
        throw new Exception($msg);
    }

    // Only update if the module is active.
    $modinfo = xarMod::getInfo($regId);
    if (!empty($modinfo) && xarModIsAvailable($modinfo['name'])) {
        // Pass to API
        if(!xarMod::apiFunc('modules', 'admin', 'updatehooks', array('regid' => $regId))) return;
    }

    if (!xarVarFetch('return_url', 'isset', $return_url, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!empty($return_url)) {
        xarResponse::redirect($return_url);
    } else {
        xarResponse::redirect(xarModURL('modules', 'admin', 'hooks',
                                      array('hook' => $curhook)));
    }
    return true;
}

?>
