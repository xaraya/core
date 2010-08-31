<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 */

function dynamicdata_admin_modifyconfig()
{
    if (!xarSecurityCheck('AdminDynamicData')) return;
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('tab','str:1', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;

    $data['module_settings'] = xarMod::apiFunc('base','admin','getmodulesettings',array('module' => 'dynamicdata'));
    $data['module_settings']->setFieldList('items_per_page, use_module_alias, use_module_icons');
    $data['module_settings']->getItem();
    switch (strtolower($phase)) {
        case 'modify':
        default:

            break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) {
                return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
            }
            if (!xarVarFetch('debugmode',    'checkbox', $debugmode, xarModVars::get('dynamicdata', 'debugmode'), XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('administrators', 'str', $administrators, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('debugusers', 'str', $candidates, '', XARVAR_NOT_REQUIRED)) return;

            $isvalid = $data['module_settings']->checkInput();
            if (!$isvalid) {
                return xarTplModule('dynamicdata','admin','modifyconfig', $data);
            } else {
                $itemid = $data['module_settings']->updateItem();
            }

            $admins = explode(',',$administrators);
            $validadmins = array();
            foreach ($admins as $admin) {
                if (empty($admin)) continue;
                $user = xarMod::apiFunc('roles','user','get',array('uname' => trim($admin)));
                if(!empty($user)) $validadmins[$user['uname']] = $user['uname'];
            }
            xarModVars::set('dynamicdata', 'administrators', serialize($validadmins));
            xarModVars::set('dynamicdata', 'debugmode', $debugmode);

            // Get the users to be shown the debug messages
            if (empty($candidates)) {
                $candidates = array();
            } else {
                $candidates = explode(',',$candidates);
            }
            $newusers = array();
            foreach ($candidates as $candidate) {
                $user = xarMod::apiFunc('roles','user','get',array('uname' => trim($candidate)));
                if(!empty($user)) $newusers[$user['uname']] = array('id' => $user['id']);
            }
            xarModVars::set('dynamicdata', 'debugusers', serialize($newusers));
            break;
    }
    return $data;
}
?>