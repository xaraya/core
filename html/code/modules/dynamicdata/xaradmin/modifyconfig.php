<?php
/**
 * Modify the configuration settings of this module
 *
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/182.html
 */

/**
 * Modify the configuration settings of this module
 *
 * Standard GUI function to display and update the configuration settings of the module based on input data.
 *
 * @return mixed data array for the template display or output display string if invalid data submitted
 */
function dynamicdata_admin_modifyconfig()
{
    // Security
    if (!xarSecurityCheck('AdminDynamicData')) return;
    
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('tab','str:1', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;

    $data['module_settings'] = xarMod::apiFunc('base','admin','getmodulesettings',array('module' => 'dynamicdata'));
    $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, use_module_icons');
    $data['module_settings']->getItem();
    switch (strtolower($phase)) {
        case 'modify':
        default:

            break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) {
                return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
            }
            if (!xarVarFetch('debugmode',    'checkbox', $debugmode, xarModVars::get('dynamicdata', 'debugmode'), XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('administrators', 'str', $administrators, '', XARVAR_NOT_REQUIRED)) return;

            $isvalid = $data['module_settings']->checkInput();
            if (!$isvalid) {
                return xarTpl::module('dynamicdata','admin','modifyconfig', $data);
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
            break;
    }
    return $data;
}
?>
