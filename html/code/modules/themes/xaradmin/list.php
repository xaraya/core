<?php
/**
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * List themes and current settings
 * @author Marty Vance
 * @author Chris Powis <crisp@xaraya.com>
 * @return array data for the template display
 */
function themes_admin_list()
{
    // Security
    if(!xarSecurityCheck('AdminThemes')) return;

    // lets regenerate the list on each reload, for now
    if(!xarMod::apiFunc('themes', 'admin', 'regenerate')) return;

    if (!xarVarFetch('phase', 'pre:trim:lower:enum:update',
        $phase, null, XARVAR_DONT_SET)) return;

    // update default themes
    if ($phase == 'update') {
        if (!xarSecConfirmAuthKey()) 
            return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
        $old_user_theme = xarModVars::get('themes', 'default_theme');
        $old_admin_theme = xarModVars::get('themes', 'admin_theme');
        if (!xarVarFetch('user_theme', 'pre:trim:lower:str:1:',
            $new_user_theme, $old_user_theme, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('admin_theme', 'pre:trim:lower:str:1:',
            $new_admin_theme, $old_admin_theme, XARVAR_NOT_REQUIRED)) return;
        if ($new_user_theme != $old_user_theme) {
            $themeid = xarThemeGetIdFromName($new_user_theme);
            if ($themeid) {
                $info = xarThemeGetInfo($themeid);
                if ($info['class'] != 2) {
                    $new_user_theme = $old_user_theme;
                } else {
                    if (xarVarIsCached('Mod.Variables.themes', 'default_theme')) 
                        xarVarDelCached('Mod.Variables.themes', 'default_theme');
                    if (!xarMod::apiFunc('themes','admin','install',array('regid'=>$themeid)))
                        $new_user_theme = $old_user_theme;
                }
            } else {
                $new_user_theme = $old_user_theme;
            }
            xarModVars::set('themes', 'default_theme', $new_user_theme);
        }
        if ($new_admin_theme != $old_admin_theme) {
            $themeid = xarThemeGetIdFromName($new_admin_theme);
            if ($themeid) {
                $info = xarThemeGetInfo($themeid);
                if ($info['class'] != 2) {
                    $new_admin_theme = $old_admin_theme;
                } else {
                    if (xarVarIsCached('Mod.Variables.themes', 'admin_theme')) 
                        xarVarDelCached('Mod.Variables.themes', 'admin_theme');
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
        $return_url = xarModURL('themes', 'admin', 'list');
        xarController::redirect($return_url);
    }

    
    // display phase     
    $data = array();
        
    if (!xarVarFetch('startnum', 'int:1:',
        $data['startnum'], 1, XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('tab', 'pre:trim:lower:enum:plain:preview',
        $data['tab'], null, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('state', 'int',
        $data['state'], null, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('class', 'int:0:4', // 0=system, 1=utility, 2=user, 3=all
        $data['class'], null, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('sort', 'pre:trim:upper:enum:ASC:DESC',
        $data['sort'], 'ASC', XARVAR_NOT_REQUIRED)) return;
    
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

    $authid = xarSecGenAuthKey();
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
        $theme['info_url'] = xarModURL('themes', 'admin', 'themesinfo',
            array('id' => $theme['regid']));
        $return_url = xarServer::getCurrentURL(array('state' => $data['state'] != XARTHEME_STATE_ANY ? XARTHEME_STATE_ANY : null), false, $theme['name']);
        $return_url = urlencode($return_url);
        switch ($theme['state']) {
            case XARTHEME_STATE_UNINITIALISED: // 1
                if ($theme['class'] != 4)
                    $theme['init_url'] = xarModURL('themes', 'admin', 'install',
                        array('id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url));
            break;
            case XARTHEME_STATE_INACTIVE: // 2
                $theme['activate_url'] = xarModURL('themes', 'admin', 'activate',
                    array('id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url));
                $theme['remove_url'] = xarModURL('themes', 'admin', 'remove',
                    array('id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url));   
            break;
            case XARTHEME_STATE_ACTIVE: // 3
                if ($theme['name'] != $data['user_theme'] && $theme['name'] != $data['admin_theme']) 
                    $theme['deactivate_url'] = xarModURL('themes', 'admin', 'deactivate',
                        array('id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url));
            break;
            case XARTHEME_STATE_UPGRADED: // 5
                $theme['upgrade_url'] = xarModURL('themes', 'admin', 'upgrade',
                    array('id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url));
            break;
            case XARTHEME_STATE_MISSING_FROM_UNINITIALISED: // 4
            case XARTHEME_STATE_MISSING_FROM_INACTIVE: // 7
            case XARTHEME_STATE_MISSING_FROM_ACTIVE: // 8
            case XARTHEME_STATE_MISSING_FROM_UPGRADED: // 9
                $theme['remove_url'] = xarModURL('themes', 'admin', 'remove',
                    array('id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url));
            break;
            
        }
        $themes[$key] = $theme;
    }

    $data['themes'] = $themes;
    $data['useicons'] = xarModVars::get('themes', 'use_module_icons');
    $data['authid'] = $authid;

    $data['states'] = array(
        XARTHEME_STATE_ANY => 
            array('id' => XARTHEME_STATE_ANY, 'name' => xarML('All')),
        XARTHEME_STATE_INSTALLED =>
            array('id' => XARTHEME_STATE_INSTALLED, 'name' => xarML('Installed')),
        XARTHEME_STATE_ACTIVE =>    
            array('id' => XARTHEME_STATE_ACTIVE, 'name' => xarML('Active')),
        XARTHEME_STATE_INACTIVE =>    
            array('id' => XARTHEME_STATE_INACTIVE, 'name' => xarML('Inactive')),
        XARTHEME_STATE_UNINITIALISED =>
            array('id' => XARTHEME_STATE_UNINITIALISED, 'name' => xarML('Uninitialized')),
        XARTHEME_STATE_MISSING_FROM_UNINITIALISED =>
            array('id' => XARTHEME_STATE_MISSING_FROM_UNINITIALISED, 'name' => xarML('Missing (Not Inited)')),
        XARTHEME_STATE_MISSING_FROM_INACTIVE => 
            array('id' => XARTHEME_STATE_MISSING_FROM_INACTIVE, 'name' => xarML('Missing (Inactive)')),
        XARTHEME_STATE_MISSING_FROM_ACTIVE =>   
            array('id' => XARTHEME_STATE_MISSING_FROM_ACTIVE, 'name' => xarML('Missing (Active)')),
        XARTHEME_STATE_MISSING_FROM_UPGRADED =>
            array('id' => XARTHEME_STATE_MISSING_FROM_UPGRADED, 'name' => xarML('Missing (Upgraded)')),
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
    if ($data['state'] == XARTHEME_STATE_ANY) {
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
?>