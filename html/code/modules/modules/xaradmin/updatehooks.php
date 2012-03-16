<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Update hooks by hook module
 *
 *
 * @author Xaraya Development Team
 */
function modules_admin_updatehooks()
{
    // Security
    if(!xarSecurityCheck('ManageModules')) {return;}

    if (!xarSecConfirmAuthKey()) {
        //return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        
    // Curhook contains module name
    if (!xarVarFetch('curhook', 'str:1:', $curhook)) {return;}

    $regId = xarMod::getRegID($curhook);
    if (!isset($curhook) || !isset($regId)) {
        $msg = xarML('Invalid hook');
        throw new Exception($msg);
    }

    if (!xarVarFetch('subjects', 'array', $subjects, null, XARVAR_NOT_REQUIRED)) return;
  
    

    $data = array();
    // Only update if the module is active.
    $modinfo = xarMod::getInfo($regId);
    if (!empty($modinfo) && xarModIsAvailable($modinfo['name'])) {
        $data['regid'] = $regId;
        if (!empty($subjects))
            $data['subjects'] = $subjects;
        if(!xarMod::apiFunc('modules', 'admin', 'updatehooks', $data)) return;
    }

    if (!xarVarFetch('return_url', 'isset', $return_url, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!empty($return_url)) {
        xarController::redirect($return_url);
    } else {
        xarController::redirect(xarModURL('modules', 'admin', 'hooks',
                                      array('hook' => $curhook)));
    }
    return true;
}

?>
