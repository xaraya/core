<?php
/**
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 */
/**
 * List themes and current settings
 * @author Marty Vance
 * @author Chris Powis <crisp@xaraya.com>
 * @return array|string|void data for the template display
 */
function themes_admin_view()
{
    // Security
    if(!xarSecurity::check('AdminThemes')) return;

    // lets regenerate the list on each reload, for now
    if(!xarMod::apiFunc('themes', 'admin', 'regenerate')) return;

    if (!xarVar::fetch('phase', 'pre:trim:lower:enum:update',
        $phase, null, xarVar::DONT_SET)) return;

    // update default themes
    if ($phase == 'update') {
        if (!xarSec::confirmAuthKey()) 
            return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
        $old_user_theme = xarModVars::get('themes', 'default_theme');
        $old_admin_theme = xarModVars::get('themes', 'admin_theme');
        if (!xarVar::fetch('user_theme', 'pre:trim:lower:str:1:',
            $new_user_theme, $old_user_theme, xarVar::NOT_REQUIRED)) return;
        if (!xarVar::fetch('admin_theme', 'pre:trim:lower:str:1:',
            $new_admin_theme, $old_admin_theme, xarVar::NOT_REQUIRED)) return;
        if ($new_user_theme != $old_user_theme) {
            $themeid = xarTheme::getIDFromName($new_user_theme);
            if ($themeid) {
                $info = xarTheme::getInfo($themeid);
                if ($info['class'] != 2) {
                    $new_user_theme = $old_user_theme;
                } else {
                    if (xarCore::isCached('Mod.Variables.themes', 'default_theme')) 
                        xarCore::delCached('Mod.Variables.themes', 'default_theme');
                    if (!xarMod::apiFunc('themes','admin','install',array('regid'=>$themeid)))
                        $new_user_theme = $old_user_theme;
                }
            } else {
                $new_user_theme = $old_user_theme;
            }
            xarModVars::set('themes', 'default_theme', $new_user_theme);
        }
        if ($new_admin_theme != $old_admin_theme) {
            $themeid = xarTheme::getIDFromName($new_admin_theme);
            if ($themeid) {
                $info = xarTheme::getInfo($themeid);
                if ($info['class'] != 2) {
                    $new_admin_theme = $old_admin_theme;
                } else {
                    if (xarCore::isCached('Mod.Variables.themes', 'admin_theme')) 
                        xarCore::delCached('Mod.Variables.themes', 'admin_theme');
                    if (!xarMod::apiFunc('themes','admin','install',array('regid'=>$themeid)))
                        $new_admin_theme = $old_admin_theme;
                }
            } else {
                $new_admin_theme = $old_admin_theme;
            }
            // catch null value on first run (2.2.x > 2.3.0)
            if (is_null($new_admin_theme))
                $new_admin_theme = $new_user_theme;
            xarModVars::set('themes', 'admin_theme', $new_admin_theme);
        }
        $return_url = xarController::URL('themes', 'admin', 'view');
        xarController::redirect($return_url);
    }
    
    // display phase     
    $data = array();
        
    if (!xarVar::fetch('startnum', 'int:1:',
        $data['startnum'], 1, xarVar::NOT_REQUIRED)) return;

    if (!xarVar::fetch('tab', 'pre:trim:lower:enum:plain:preview',
        $data['tab'], null, xarVar::DONT_SET)) return;
    if (!xarVar::fetch('state', 'int',
        $data['state'], null, xarVar::DONT_SET)) return;
    if (!xarVar::fetch('class', 'int:0:4', // 0=system, 1=utility, 2=user, 3=all
        $data['class'], null, xarVar::DONT_SET)) return;
    if (!xarVar::fetch('sort', 'pre:trim:upper:enum:ASC:DESC',
        $data['sort'], 'ASC', xarVar::NOT_REQUIRED)) return;
    
    if (!isset($data['tab']))
        $data['tab'] = xarModUserVars::get('themes', 'selstyle');
    if (!isset($data['state']))
        $data['state'] = xarModUserVars::get('themes', 'selfilter');
    if (!isset($data['class']))
        $data['class'] = xarModUserVars::get('themes', 'selclass');
    // support legacy use of class name instead of id (2.2.x > 2.3.0)
    if (isset($data['class']) && ( !is_numeric($data['class']) && is_string($data['class']) ))
        $data['class'] = strtr($data['class'], array('system' => 0, 'utility' => 1, 'user' => 2, 'all' => 3));
    
    $data['items_per_page'] = xarModVars::get('themes', 'items_per_page');

    $authid = xarSec::genAuthKey();
    $themes = xarMod::apiFunc('themes', 'admin', 'getitems',
        array(
            'state' => $data['state'],
            'class' => $data['class'],
            'startnum' => $data['startnum'],
            'numitems' => $data['items_per_page'],
            'sort' => 'name ' . $data['sort'],
        ));

    $data['total'] = xarMod::apiFunc('themes', 'admin', 'countitems',
        array(
            'state' => $data['state'],
            'class' => $data['class'],
        ));        

    $data['user_theme'] = xarModVars::get('themes', 'default_theme');
    $data['admin_theme'] = xarModVars::get('themes', 'admin_theme');

    foreach ($themes as $key => $theme) {
        $theme['info_url'] = xarController::URL('themes', 'admin', 'themesinfo',
            array('id' => $theme['regid']));
        $return_url = xarServer::getCurrentURL(array('state' => $data['state'] != xarTheme::STATE_ANY ? xarTheme::STATE_ANY : null), false, $theme['name']);
        $return_url = urlencode($return_url);
        switch ($theme['state']) {
            case xarTheme::STATE_UNINITIALISED: // 1
                if ($theme['class'] != 4)
                    $theme['init_url'] = xarController::URL('themes', 'admin', 'install',
                        array('id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url));
            break;
            case xarTheme::STATE_INACTIVE: // 2
                $theme['activate_url'] = xarController::URL('themes', 'admin', 'activate',
                    array('id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url));
                $theme['remove_url'] = xarController::URL('themes', 'admin', 'remove',
                    array('id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url));   
            break;
            case xarTheme::STATE_ACTIVE: // 3
                if ($theme['name'] != $data['user_theme'] && $theme['name'] != $data['admin_theme']) 
                    $theme['deactivate_url'] = xarController::URL('themes', 'admin', 'deactivate',
                        array('id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url));
            break;
            case xarTheme::STATE_UPGRADED: // 5
                $theme['upgrade_url'] = xarController::URL('themes', 'admin', 'upgrade',
                    array('id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url));
            break;
            case xarTheme::STATE_MISSING_FROM_UNINITIALISED: // 4
            case xarTheme::STATE_MISSING_FROM_INACTIVE: // 7
            case xarTheme::STATE_MISSING_FROM_ACTIVE: // 8
            case xarTheme::STATE_MISSING_FROM_UPGRADED: // 9
                $theme['remove_url'] = xarController::URL('themes', 'admin', 'remove',
                    array('id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url));
            break;
            
        }
        $themes[$key] = $theme;
    }

    $data['themes'] = $themes;
    $data['useicons'] = xarModVars::get('themes', 'use_module_icons');
    $data['authid'] = $authid;

    $data['states'] = array(
        xarTheme::STATE_ANY => 
            array('id' => xarTheme::STATE_ANY, 'name' => xarML('All')),
        xarTheme::STATE_INSTALLED =>
            array('id' => xarTheme::STATE_INSTALLED, 'name' => xarML('Installed')),
        xarTheme::STATE_ACTIVE =>    
            array('id' => xarTheme::STATE_ACTIVE, 'name' => xarML('Active')),
        xarTheme::STATE_INACTIVE =>    
            array('id' => xarTheme::STATE_INACTIVE, 'name' => xarML('Inactive')),
        xarTheme::STATE_UNINITIALISED =>
            array('id' => xarTheme::STATE_UNINITIALISED, 'name' => xarML('Uninitialized')),
        xarTheme::STATE_MISSING_FROM_UNINITIALISED =>
            array('id' => xarTheme::STATE_MISSING_FROM_UNINITIALISED, 'name' => xarML('Missing (Not Inited)')),
        xarTheme::STATE_MISSING_FROM_INACTIVE => 
            array('id' => xarTheme::STATE_MISSING_FROM_INACTIVE, 'name' => xarML('Missing (Inactive)')),
        xarTheme::STATE_MISSING_FROM_ACTIVE =>   
            array('id' => xarTheme::STATE_MISSING_FROM_ACTIVE, 'name' => xarML('Missing (Active)')),
        xarTheme::STATE_MISSING_FROM_UPGRADED =>
            array('id' => xarTheme::STATE_MISSING_FROM_UPGRADED, 'name' => xarML('Missing (Upgraded)')),
    );
          
    $data['classes'] = array(
        3 => array('id' => 3, 'name' => xarML('All')),
        0 => array('id' => 0, 'name' => xarML('System')),
        1 => array('id' => 1, 'name' => xarML('Utility')),
        2 => array('id' => 2, 'name' => xarML('User')),
        4 => array('id' => 4, 'name' => xarML('Core')),
    );
    
    $data['tabs'] = array(
        array('id' => 'plain', 'name' => xarML('Plain')),
        array('id' => 'preview', 'name' => xarML('Preview')),
    );

    // remember filter selections for current user 
    xarModUserVars::set('themes', 'selstyle', $data['tab']);
    xarModUserVars::set('themes', 'selfilter', $data['state']);
    xarModUserVars::set('themes', 'selclass', $data['class']);
     
    $count = count($themes);
    if ($data['state'] == xarTheme::STATE_ANY) {
        if ($data['class'] == 3) {
            $searched = xarML('Showing #(1) themes', $count);
        } else {
            $searched = xarML('Showing #(1) #(2) class themes', $count, $data['classes'][$data['class']]['name']);
        }
    } else {
        if ($data['class'] == 3) {
            $searched = xarML('Showing #(1) themes in #(2) state', $count, $data['states'][$data['state']]['name']);
        } else {
            $searched = xarML('Showing #(1) #(2) class themes in #(3) state', $count, $data['classes'][$data['class']]['name'], $data['states'][$data['state']]['name']);
        }
    }
    $data['searched'] = $searched;        

    return $data;
}
