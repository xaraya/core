<?php
/**
 * Modify the configuration settings of this module
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
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
    if (!xarSecurity::check('AdminDynamicData')) {
        return;
    }

    $data = [];
    if (!xarVar::fetch('phase', 'str:1:100', $phase, 'modify', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY)) {
        return;
    }
    if (!xarVar::fetch('tab', 'str:1', $data['tab'], 'general', xarVar::NOT_REQUIRED)) {
        return;
    }

    $data['module_settings'] = xarMod::apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'dynamicdata']);
    $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, use_module_icons');
    $data['module_settings']->getItem();
    switch (strtolower($phase)) {
        case 'modify':
        default:

            break;

        case 'update':
            // Confirm authorisation code
            if (!xarSec::confirmAuthKey()) {
                return xarTpl::module('privileges', 'user', 'errors', ['layout' => 'bad_author']);
            }
            if (!xarVar::fetch('debugmode', 'checkbox', $debugmode, xarModVars::get('dynamicdata', 'debugmode'), xarVar::NOT_REQUIRED)) {
                return;
            }
            if (!xarVar::fetch('show_queries', 'checkbox', $show_queries, xarConfigVars::get(null, 'Site.BL.ShowQueries'), xarVar::NOT_REQUIRED)) {
                return;
            }
            if (!xarVar::fetch('suppress_updates', 'checkbox', $suppress_updates, false, xarVar::NOT_REQUIRED)) {
                return;
            }
            //            if (!xarVar::fetch('administrators', 'str', $administrators, '', xarVar::NOT_REQUIRED)) return;
            if (!xarVar::fetch('caching', 'checkbox', $caching, xarModVars::get('dynamicdata', 'caching'), xarVar::NOT_REQUIRED)) {
                return;
            }

            $isvalid = $data['module_settings']->checkInput();
            if (!$isvalid) {
                return xarTpl::module('dynamicdata', 'admin', 'modifyconfig', $data);
            } else {
                $itemid = $data['module_settings']->updateItem();
            }

            /*
                        $admins = explode(',',$administrators);
                        $validadmins = array();
                        foreach ($admins as $admin) {
                            if (empty($admin)) continue;
                            $user = xarMod::apiFunc('roles','user','get',array('uname' => trim($admin)));
                            if(!empty($user)) $validadmins[$user['uname']] = $user['uname'];
                        }
                        xarModVars::set('dynamicdata', 'administrators', serialize($validadmins));
            */
            xarModVars::set('dynamicdata', 'debugmode', $debugmode);
            xarConfigVars::set(null, 'Site.BL.ShowQueries', $show_queries);
            xarModVars::set('dynamicdata', 'suppress_updates', $suppress_updates);
            xarModVars::set('dynamicdata', 'caching', $caching);
            break;
    }
    return $data;
}
