<?php
/**
 * Modify the configuration parameters
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
 * This is a standard function to modify the configuration parameters of the
 * module
 *
 * @author Marty Vance
 */
function themes_admin_modifyconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminTheme')) return;

    if (!xarVarFetch('phase',        'str:1:100', $phase,       'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('sitename', 'str:1:', $data['sitename'], xarModVars::get('themes', 'SiteName'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('separator', 'str:1:', $data['separator'], xarModVars::get('themes', 'SiteTitleSeparator'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pagetitle', 'str:1:', $data['pagetitle'], 'default', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('showphpcbit', 'checkbox', $data['showphpcbit'], xarModVars::get('themes', 'ShowPHPCommentBlockInTemplates'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('showtemplates', 'checkbox', $data['showtemplates'], xarModVars::get('themes', 'ShowTemplates'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('cachetemplates', 'checkbox', $data['cachetemplates'], xarConfigVars::get(null, 'Site.BL.CacheTemplates'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('variable_dump', 'checkbox', $data['variable_dump'], xarModVars::get('themes', 'variable_dump'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('slogan', 'str::', $data['slogan'], xarModVars::get('themes', 'SiteSlogan'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('footer', 'str:1:', $data['footer'], xarModVars::get('themes', 'SiteFooter'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('copyright', 'str:1:', $data['copyright'], xarModVars::get('themes', 'SiteCopyRight'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('AtomTag', 'str:1:', $data['atomtag'], xarModVars::get('themes', 'AtomTag'), XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('usedashboard', 'checkbox', $data['usedashboard'], xarModVars::get('themes', 'usedashboard'), XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('adminpagemenu', 'checkbox', $data['adminpagemenu'], xarModVars::get('themes', 'adminpagemenu'), XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('dashtemplate', 'str:1:', $data['dashtemplate'], trim(xarModVars::get('themes', 'dashtemplate')), XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('themedir','str:1:',$data['defaultThemeDir'],'themes',XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('selsort','str:1:',$data['selsort'],'plain',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('selfilter','int',$data['selfilter'],XARMOD_STATE_ANY,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('hidecore', 'checkbox', $data['hidecore'], false, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('selstyle','str:1:',$data['selstyle'],'plain',XARVAR_NOT_REQUIRED)) return;

    // Dashboard
    if (!isset($data['dashtemplate']) || trim($data['dashtemplate']=='')) {
        $data['dashtemplate']='dashboard';
    }

    $data['module_settings'] = xarModAPIFunc('base','admin','getmodulesettings',array('module' => 'themes'));
    $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls, enable_user_menu');
    $data['module_settings']->getItem();
    switch (strtolower($phase)) {
        case 'modify':
        default:
            break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;        
            $isvalid = $data['module_settings']->checkInput();
            if (!$isvalid) {
                return xarTplModule('themes','admin','modifyconfig', $data);        
            } else {
                $itemid = $data['module_settings']->updateItem();
            }
            xarModVars::set('themes', 'SiteName', $data['sitename']);
            xarModVars::set('themes', 'SiteTitleSeparator', $data['separator']);
            xarModVars::set('themes', 'SiteTitleOrder', $data['pagetitle']);
            xarModVars::set('themes', 'SiteSlogan', $data['slogan']);
            xarModVars::set('themes', 'SiteCopyRight', $data['copyright']);
            xarModVars::set('themes', 'SiteFooter', $data['footer']);
            xarModVars::set('themes', 'ShowPHPCommentBlockInTemplates', $data['showphpcbit']);
            xarModVars::set('themes', 'ShowTemplates', $data['showtemplates']);
            xarModVars::set('themes', 'AtomTag', $data['atomtag']);
            xarModVars::set('themes', 'variable_dump', $data['variable_dump']);
            xarModVars::set('themes', 'usedashboard', $data['usedashboard']);
            xarModVars::set('themes', 'adminpagemenu', $data['adminpagemenu']);
            xarModVars::set('themes', 'dashtemplate', $data['dashtemplate']);
            xarConfigVars::set(null,'Site.BL.ThemesDirectory', $data['defaultThemeDir']);
            xarConfigVars::set(null, 'Site.BL.CacheTemplates',$data['cachetemplates']);
            xarModVars::set('themes', 'hidecore', $data['hidecore']);
            xarModVars::set('themes', 'selstyle', $data['selstyle']);
            xarModVars::set('themes', 'selfilter', $data['selfilter']);
            xarModVars::set('themes', 'selsort', $data['selsort']);

            // Adjust the usermenu hook according to the setting
            sys::import('xaraya.structures.hooks.observer');
            $observer = new BasicObserver('themes','user','usermenu');
            $subject = new HookSubject('roles');
            if (xarModVars::get('themes','enable_user_menu')) {
                $subject->attach($observer);
            } else {
                $subject->detach($observer);
            }
            break;
    }
    return $data;
}
?>