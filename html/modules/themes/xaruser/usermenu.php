<?php
/**
 * main themes module user function
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
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
            $data['icon'] = xarTplGetImage('themes.png','base');
            $data['link'] = xarModURL('roles', 'user', 'account', array('moduleload' => 'themes'));
            $data['label'] = xarML('Choose Theme');
            return (serialize($data));                                                                         
            break;
        case 'form':
            // Get list of themes
            $filter['Class'] = 2;
            $data['themes'] = xarModAPIFunc('themes',
                'admin',
                'getlist',
                $filter);

            $defaulttheme = xarModUserVars::get('themes', 'default');

            $name = xarUserGetVar('name');
            $id = xarUserGetVar('id');
            $authid = xarSecGenAuthKey('themes');
            $data = array('authid' => $authid,
                    'name' => $name,
                    'id' => $id,
                    'defaulttheme' => $defaulttheme,
                    'themes' => $data['themes']);
                                  return serialize($data);
            break;

        case 'update':
            if (!xarVarFetch('id', 'int:1:', $id)) return;
            if (!xarVarFetch('defaulttheme', 'str:1:100', $defaulttheme, '', XARVAR_NOT_REQUIRED)) return;
            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) {
                return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
            }        
            $themeInfo = xarThemeGetInfo($defaulttheme);

            xarModUserVars::set('themes', 'default', $themeInfo['name'], $id);
            // Redirect
            xarResponse::Redirect(xarModURL('roles', 'user', 'account', array('moduleload' => 'themes')));

            break;
    }

    return $data;
}

?>
