<?php
/**
 * Modify the configuration settings of this module
 *
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * Modify the configuration settings of this module
 *
 * Standard GUI function to display and update the configuration settings of the module based on input data.
 * 
 * @return mixed data array for the template display or output display string if invalid data submitted
 */
function authsystem_admin_modifyconfig()
{
    // Security
    if (!xarSecurityCheck('AdminAuthsystem')) return;
    
    if (!xarVarFetch('phase',        'str:1:100', $phase,       'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('uselockout',   'checkbox',  $data['uselockout'],  xarModVars::get('authsystem', 'uselockout'),     XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('lockouttime',  'int:1:',    $data['lockouttime'], (int)xarModVars::get('authsystem', 'lockouttime'),       XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('lockouttries', 'int:1:',    $data['lockouttries'], (int)xarModVars::get('authsystem', 'lockouttries'),       XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;

    $data['module_settings'] = xarMod::apiFunc('base','admin','getmodulesettings',array('module' => 'authsystem'));
    $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls');
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
            $isvalid = $data['module_settings']->checkInput();
            if (!$isvalid) {
                return xarTpl::module('authsystem','admin','modifyconfig', $data);        
            } else {
                $itemid = $data['module_settings']->updateItem();
            }
            xarModVars::set('authsystem', 'uselockout', $data['uselockout']);
            xarModVars::set('authsystem', 'lockouttime', $data['lockouttime']);
            xarModVars::set('authsystem', 'lockouttries', $data['lockouttries']);
            break;
    }
    return $data;
}
?>
