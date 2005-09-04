<?php

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
    if (!xarVarFetch('defaultmodule','str:1:',$defaultModuleName)) return;
    if (!xarVarFetch('defaulttype','str:1:',$defaultModuleType)) return;
    if (!xarVarFetch('defaultfunction','str:1:',$defaultModuleFunction,'main',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('shorturl','checkbox',$enableShortURLs,false,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('htmlenitites','checkbox',$FixHTMLEntities,false,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('themedir','str:1:',$defaultThemeDir,'themes',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('securitylevel','str:1:',$securityLevel)) return;
    if (!xarVarFetch('sessionduration','int:1:',$sessionDuration,30,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('sessiontimeout','int:1:',$sessionTimeout,10,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('loadlegacy','checkbox',$loadLegacy,true,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('secureserver','checkbox',$secureServer,true,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('defaultlocale','str:1:',$defaultLocale)) return;
//    if (!xarVarFetch('localeslist','str:1:',$localesList)) return;

    if (!xarSecConfirmAuthKey()) return;

    // Security Check
    if(!xarSecurityCheck('AdminBase')) return;

    if (!isset($cacheTemplates)) {
        $cacheTemplates = true;
    }


    // TODO move this to the API once complete.
    xarConfigSetVar('Site.Core.LoadLegacy', $loadLegacy);
    xarConfigSetVar('Site.Core.EnableSecureServer', $secureServer);
    xarConfigSetVar('Site.BL.ThemesDirectory', $defaultThemeDir);
    // FIXME: Where has this moved to??? It's not settable now, very inconvenient
    //xarConfigSetVar('Site.BL.CacheTemplates', $cacheTemplates);
    xarConfigSetVar('Site.Core.DefaultModuleName', $defaultModuleName);
    xarConfigSetVar('Site.Core.DefaultModuleType', $defaultModuleType);
    xarConfigSetVar('Site.Core.DefaultModuleFunction', $defaultModuleFunction);
    xarConfigSetVar('Site.Core.EnableShortURLsSupport', $enableShortURLs);
    xarConfigSetVar('Site.Core.FixHTMLEntities', $FixHTMLEntities);

    //Filtering Options
    // Security Levels
    xarConfigSetVar('Site.Session.SecurityLevel', $securityLevel);
    xarConfigSetVar('Site.Session.Duration', $sessionDuration);
    xarConfigSetVar('Site.Session.InactivityTimeout', $sessionTimeout);

    // Locales
    xarConfigSetVar('Site.MLS.DefaultLocale', $defaultLocale);

    //$authModules = array('authsystem');
    //xarConfigSetVar('Site.User.AuthenticationModules',$authModules);

    xarResponseRedirect(xarModURL('base', 'admin', 'modifyconfig'));

    return true;
}

?>
