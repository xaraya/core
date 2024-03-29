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
 * Update hooks by hook module
 *
 *
 * @author Xaraya Development Team
 */
function modules_admin_updatehooks()
{
    // Security
    if(!xarSecurity::check('ManageModules')) {return;}

    if (!xarSec::confirmAuthKey()) {
        //return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        
    // Curhook contains module name
    if (!xarVar::fetch('curhook', 'str:1:', $curhook)) {return;}

    $regId = xarMod::getRegID($curhook);
    if (!isset($curhook) || !isset($regId)) {
        $msg = xarML('Invalid hook');
        throw new Exception($msg);
    }

    if (!xarVar::fetch('subjects', 'array', $subjects, null, xarVar::NOT_REQUIRED)) return;
  
    

    $data = array();
    // Only update if the module is active.
    $modinfo = xarMod::getInfo($regId);
    if (!empty($modinfo) && xarMod::isAvailable($modinfo['name'])) {
        $data['regid'] = $regId;
        if (!empty($subjects))
            $data['subjects'] = $subjects;
        if(!xarMod::apiFunc('modules', 'admin', 'updatehooks', $data)) return;
    }

    if (!xarVar::fetch('return_url', 'isset', $return_url, '', xarVar::NOT_REQUIRED)) {return;}
    if (!empty($return_url)) {
        xarController::redirect($return_url);
    } else {
        xarController::redirect(xarController::URL('modules', 'admin', 'hooks',
                                      array('hook' => $curhook)));
    }
    return true;
}
