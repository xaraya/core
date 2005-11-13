<?php
/**
 * main themes module user function
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */

/**
 * main themes module function
 * @author Marty Vance
 * @return themes _admin_main
 */
function themes_user_usermenu($args)
{
    extract($args);
    // Security Check
    if (!xarSecurityCheck('ViewThemes',0)) return '';

    if(!xarVarFetch('phase','notempty', $phase, 'menu', XARVAR_NOT_REQUIRED)) {return;}
    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Your Account Preferences')));
    switch(strtolower($phase)) {
        case 'menu':

            $icon = 'modules/themes/xarimages/themes.gif';
            $current = xarModURL('roles', 'user', 'account', array('moduleload' => 'themes'));
            $data = xarTplModule('themes', 'user', 'usermenu_icon', array('icon' => $icon, 'current' => $current));

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
            $uid = xarUserGetVar('uid');
            $authid = xarSecGenAuthKey('themes');
            $data = xarTplModule('themes', 'user', 'usermenu_form', array('authid' => $authid,
                    'name' => $name,
                    'uid' => $uid,
                    'defaulttheme' => $defaulttheme,
                    'themes' => $data['themes']));
            break;

        case 'update':
            if (!xarVarFetch('uid', 'int:1:', $uid)) return;
            if (!xarVarFetch('defaulttheme', 'str:1:100', $defaulttheme, '', XARVAR_NOT_REQUIRED)) return;
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

?>
