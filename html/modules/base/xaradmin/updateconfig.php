<?php
/**
 * File: $Id
 *
 * Update site configuration
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage base
 * @author John Robeson
 * @author Greg Allan
 */
/**
 * Update site configuration
 *
 * @param string
 * @return void?
 * @todo move in timezone var when we support them
 * @todo decide whether a site admin can set allowed locales for users
 * @todo update auth system part when we figure out how to do it
 * @todo add decent validation
 */
function base_admin_updateconfig()
{
    if (!xarSecConfirmAuthKey()) return;

    // Security Check
    if(!xarSecurityCheck('AdminBase')) return;

    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;
    switch ($data['tab']) {
        case 'display':
            if (!xarVarFetch('defaultmodule','str:1:',$defaultModuleName)) return;
            if (!xarVarFetch('defaulttype','str:1:',$defaultModuleType)) return;
            if (!xarVarFetch('defaultfunction','str:1:',$defaultModuleFunction,'main',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('shorturl','checkbox',$enableShortURLs,false,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('htmlenitites','checkbox',$FixHTMLEntities,false,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('themedir','str:1:',$defaultThemeDir,'themes',XARVAR_NOT_REQUIRED)) return;
            xarConfigSetVar('Site.BL.ThemesDirectory', $defaultThemeDir);
            // FIXME: Where has this moved to??? It's not settable now, very inconvenient
            //xarConfigSetVar('Site.BL.CacheTemplates', $cacheTemplates);
            xarConfigSetVar('Site.Core.DefaultModuleName', $defaultModuleName);
            xarConfigSetVar('Site.Core.DefaultModuleType', $defaultModuleType);
            xarConfigSetVar('Site.Core.DefaultModuleFunction', $defaultModuleFunction);
            xarConfigSetVar('Site.Core.EnableShortURLsSupport', $enableShortURLs);
            // enable short urls for the base module itself too
            xarModSetVar('base','SupportShortURLs', ($enableShortURLs ? 1 : 0));
            xarConfigSetVar('Site.Core.FixHTMLEntities', $FixHTMLEntities);
            break;
        case 'security':
            if (!xarVarFetch('secureserver','checkbox',$secureServer,true,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('securitylevel','str:1:',$securityLevel)) return;
            if (!xarVarFetch('sessionduration','int:1:',$sessionDuration,30,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('sessiontimeout','int:1:',$sessionTimeout,10,XARVAR_NOT_REQUIRED)) return;

            xarConfigSetVar('Site.Core.EnableSecureServer', $secureServer);

            //Filtering Options
            // Security Levels
            xarConfigSetVar('Site.Session.SecurityLevel', $securityLevel);
            xarConfigSetVar('Site.Session.Duration', $sessionDuration);
            xarConfigSetVar('Site.Session.InactivityTimeout', $sessionTimeout);
            break;
        case 'locales':
            if (!xarVarFetch('defaultlocale','str:1:',$defaultLocale)) return;
            if (!xarVarFetch('active','isset',$active)) return;
            if (!xarVarFetch('mlsmode','str:1:',$MLSMode,'SINGLE',XARVAR_NOT_REQUIRED)) return;

            $localesList = array();
            foreach($active as $activelocale) $localesList[] = $activelocale;
            if (!in_array($defaultLocale,$localesList)) $localesList[] = $defaultLocale;
            sort($localesList);

            // Locales
            xarConfigSetVar('Site.MLS.MLSMode', $MLSMode);
            xarConfigSetVar('Site.MLS.DefaultLocale', $defaultLocale);
            xarConfigSetVar('Site.MLS.AllowedLocales', $localesList);
            break;
        case 'other':
            if (!xarVarFetch('loadlegacy','checkbox',$loadLegacy,true,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('proxyhost','str:1:',$proxyhost,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('proxyport','int:1:',$proxyport,0,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('editor','str:1:',$editor,'none',XARVAR_NOT_REQUIRED)) return;

       // Save these in normal module variables for now
            xarModSetVar('base','proxyhost',$proxyhost);
            xarModSetVar('base','proxyport',$proxyport);

            xarConfigSetVar('Site.Core.LoadLegacy', $loadLegacy);
            xarModSetVar('base','editor',$editor);
            break;
    }



    //FIXME: what is this?
    if (!isset($cacheTemplates)) {
        $cacheTemplates = true;

    //$authModules = array('authsystem');
    //xarConfigSetVar('Site.User.AuthenticationModules',$authModules);

    // Call updateconfig hooks
    xarModCallHooks('module','updateconfig','base', array('module' => 'base'));

    }
    xarResponseRedirect(xarModURL('base', 'admin', 'modifyconfig',array('tab' => $data['tab'])));

    return true;
}

?>
