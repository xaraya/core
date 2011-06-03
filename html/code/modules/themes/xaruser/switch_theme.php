<?php
function themes_user_switch_theme(Array $args=array())
{
    if (!xarUserIsLoggedIn() ||
        (bool) xarModVars::get('themes', 'enable_user_menu') == false) 
        return xarTpl::module('privileges', 'user', 'error', array('layout' => 'bad_author'));
        
    if (!xarVarFetch('phase', 'pre:trim:lower:enum:update',
        $phase, 'form', XARVAR_NOT_REQUIRED)) return;

    $data = array();

    if (!xarVarFetch('return_url', 'pre:trim:str:1',
        $data['return_url'], xarServer::getCurrentURL(), XARVAR_NOT_REQUIRED)) return;   

    sys::import('modules.dynamicdata.class.properties.master');
    $data['user_themes'] = DataPropertyMaster::getProperty(array('name' => 'dropdown'));
    $data['user_themes']->options = xarMod::apiFunc('themes', 'user', 'dropdownlist');
    $data['user_themes']->value = xarModUserVars::get('themes', 'default_theme');
    
    if ($phase == 'update') {
        if (!xarSecConfirmAuthKey())
            return xarTpl::module('privileges', 'user', 'error', array('layout' => 'bad_author'));
        $isvalid = $data['user_themes']->checkInput('default_theme');
        if ($isvalid) {
            xarModUserVars::set('themes', 'default_theme', $data['user_themes']->value);
            xarController::redirect($data['return_url']);
        }
    }
    
    return $data;
}
?>