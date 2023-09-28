<?php
/**
 * Modify the configuration settings of this module
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/42.html
 */

/**
 * Modify the configuration settings of this module
 *
 * Standard GUI function to display and update the configuration settings of the module based on input data.
 * 
 * @return array<mixed>|string|void Returns display template data on success else an output string will be returned.
 */
function authsystem_admin_modifyconfig()
{
    // Security
    if (!xarSecurity::check('AdminAuthsystem')) return;

    $data = array();
    if (!xarVar::fetch('phase',           'str:1:100', $phase,                  'modify', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY)) return;
    if (!xarVar::fetch('uselockout',      'checkbox',  $data['uselockout'],      xarModVars::get('authsystem', 'uselockout'),     xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('lockouttime',     'int:1:',    $data['lockouttime'],     (int)xarModVars::get('authsystem', 'lockouttime'),       xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY)) return;
    if (!xarVar::fetch('lockouttries',    'int:1:',    $data['lockouttries'],    (int)xarModVars::get('authsystem', 'lockouttries'),       xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY)) return;
    if (!xarVar::fetch('forwarding_page', 'str',       $data['forwarding_page'], xarModVars::get('authsystem', 'forwarding_page'),       xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY)) return;
    if (!xarVar::fetch('ask_forward',     'checkbox',  $data['ask_forward'],     xarModVars::get('authsystem', 'ask_forward'),     xarVar::NOT_REQUIRED)) return;

    $data['module_settings'] = xarMod::apiFunc('base','admin','getmodulesettings',array('module' => 'authsystem'));
    $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls, frontend_page');
    $data['module_settings']->getItem();
    
    switch (strtolower($phase)) {
        case 'modify':
        default:
            break;

        case 'update':
            // Confirm authorisation code. AJAX calls ignore this
            if (!xarSec::confirmAuthKey()) {
                return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
            }        
            $isvalid = $data['module_settings']->checkInput();
            if (!$isvalid) {
                // If this is an AJAX call, send back a message (and end)
                xarController::$request->msgAjax($data['module_settings']->getInvalids());
                // No AJAX, just send the data to the template for display
                return xarTpl::module('authsystem','admin','modifyconfig', $data);        
            } else {
                $itemid = $data['module_settings']->updateItem();
            }
            xarModVars::set('authsystem', 'forwarding_page', $data['forwarding_page']);
            xarModVars::set('authsystem', 'ask_forward', $data['ask_forward']);
            xarModVars::set('authsystem', 'uselockout', $data['uselockout']);
            xarModVars::set('authsystem', 'lockouttime', $data['lockouttime']);
            xarModVars::set('authsystem', 'lockouttries', $data['lockouttries']);
            
            // If this is an AJAX call, end here
            xarController::$request->exitAjax();
            xarController::redirect(xarServer::getCurrentURL());
            return true;
    }
    return $data;
}
