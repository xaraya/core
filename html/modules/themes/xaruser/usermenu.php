<?php

/**
 * main themes module function
 * @return themes_admin_main
 *
 */
function themes_user_usermenu()
{
    
    // Security Check
	if (xarSecurityCheck('ViewThemes')) {
        
        $phase = xarVarCleanFromInput('phase');
        
        xarTplSetPageTitle(xarModGetVar('themes', 'SiteName').' :: '.
                           xarVarPrepForDisplay(xarML('Themes'))
                           .' :: '.xarVarPrepForDisplay(xarML('Your Account Preferences')));
        
        if (empty($phase)){
            $phase = 'menu';
        }
        
        switch(strtolower($phase)) {
        case 'menu':
            
            $icon = 'modules/themes/xarimages/themes.gif';
            $data = xarTplModule('themes','user', 'usermenu_icon', array('icon'    => $icon));
            
            break;
            
        case 'form':
            
            // Get list of themes
            $filter['Class'] = 2;
            $data['themes'] = xarModAPIFunc('themes',
                                            'admin',
                                            'getlist',
                                            $filter);
            
            $defaulttheme = xarModGetUserVar('themes', 'default');
            
            $name = xarUserGetVar('name');
            $uid  = xarUserGetVar('uid');
            $authid = xarSecGenAuthKey();
            $data = xarTplModule('themes','user', 'usermenu_form', array('authid'   => $authid,
                                                                         'name'     => $name,
                                                                         'uid'      => $uid,
                                                                         'defaulttheme' => $defaulttheme,
                                                                         'themes'   => $data['themes']));
            break;
            
        case 'update':
            list($uid,
                 $defaulttheme) = xarVarCleanFromInput('uid',
                                                       'defaulttheme');
            
            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;
            $themeInfo = xarThemeGetInfo($defaulttheme);
            
            
            xarModSetUserVar('themes', 'default', $themeInfo['name'], $uid);
            
            // Redirect
            xarResponseRedirect(xarModURL('roles', 'user', 'account'));
            
            break;
        }
        
        return $data;
    }
}
?>
