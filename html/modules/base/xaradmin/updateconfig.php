<?php
/**
 * Update site configuration
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 */
/**
 * Update site configuration
 *
 * @param string tab
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
            if (!xarVarFetch('alternatepagetemplate','checkbox',$alternatePageTemplate,false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('alternatepagetemplatename','str',$alternatePageTemplateName,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('defaulttype','str:1:',$defaultModuleType)) return;
            if (!xarVarFetch('defaultfunction','str:1:',$defaultModuleFunction,'main',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('shorturl','checkbox',$enableShortURLs,false,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('baseshorturl','checkbox',$enableBaseShortURLs,false,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('htmlenitites','checkbox',$FixHTMLEntities,false,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('themedir','str:1:',$defaultThemeDir,'themes',XARVAR_NOT_REQUIRED)) return;
            xarConfigSetVar('Site.BL.ThemesDirectory', $defaultThemeDir);
 
            xarConfigSetVar('Site.Core.DefaultModuleName', $defaultModuleName);
            xarModSetVar('base','UseAlternatePageTemplate', ($alternatePageTemplate ? 1 : 0));
            xarModSetVar('base','AlternatePageTemplateName', $alternatePageTemplateName);
            xarConfigSetVar('Site.Core.DefaultModuleType', $defaultModuleType);
            xarConfigSetVar('Site.Core.DefaultModuleFunction', $defaultModuleFunction);
            xarConfigSetVar('Site.Core.EnableShortURLsSupport', $enableShortURLs);
            // enable short urls for the base module itself too
            xarModSetVar('base','SupportShortURLs', ($enableBaseShortURLs ? 1 : 0));
            xarConfigSetVar('Site.Core.FixHTMLEntities', $FixHTMLEntities);
            break;
        case 'security':
            if (!xarVarFetch('secureserver','checkbox',$secureServer,true,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('securitylevel','str:1:',$securityLevel)) return;
            if (!xarVarFetch('sessionduration','int:1:',$sessionDuration,30,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('sessiontimeout','int:1:',$sessionTimeout,10,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('authmodule_order','str:1:',$authmodule_order,'',XARVAR_NOT_REQUIRED)) {return;}
            if (!xarVarFetch('cookiename','str:1:',$cookieName,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('cookiepath','str:1:',$cookiePath,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('cookiedomain','str:1:',$cookieDomain,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('referercheck','str:1:',$refererCheck,'',XARVAR_NOT_REQUIRED)) return;

            xarConfigSetVar('Site.Core.EnableSecureServer', $secureServer);

            //Filtering Options
            // Security Levels
            xarConfigSetVar('Site.Session.SecurityLevel', $securityLevel);
            xarConfigSetVar('Site.Session.Duration', $sessionDuration);
            xarConfigSetVar('Site.Session.InactivityTimeout', $sessionTimeout);
            xarConfigSetVar('Site.Session.CookieName', $cookieName);
            xarConfigSetVar('Site.Session.CookiePath', $cookiePath);
            xarConfigSetVar('Site.Session.CookieDomain', $cookieDomain);
            xarConfigSetVar('Site.Session.RefererCheck', $refererCheck);

            // Authentication modules
            if (!empty($authmodule_order)) {
                $authmodules = explode(';', $authmodule_order);
                xarConfigSetVar('Site.User.AuthenticationModules', $authmodules);
            }
            break;
        case 'locales':
            if (!xarVarFetch('defaultlocale','str:1:',$defaultLocale)) return;
            if (!xarVarFetch('active','isset',$active)) return;
            if (!xarVarFetch('mlsmode','str:1:',$MLSMode,'SINGLE',XARVAR_NOT_REQUIRED)) return;

            $localesList = array();
            foreach($active as $activelocale) $localesList[] = $activelocale;
            if (!in_array($defaultLocale,$localesList)) $localesList[] = $defaultLocale;
            sort($localesList);

            if ($MLSMode == 'UNBOXED') {
                if ((!function_exists('mb_http_input')) || (version_compare(phpversion(), "4.3.0", "<"))) {
                    $msg = xarML('You cannot use UNBOXED mode of the MultiLanguage system unless you have php 4.3.0 with the mbstring extension compiled in');
                    xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
                    break;
                }
                if (xarMLSGetCharsetFromLocale($defaultLocale) != 'utf-8') {
                    $msg = xarML('You should select utf-8 locale as default before selecting UNBOXED mode');
                    xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
                    break;
                }
            }

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

            // Timezone, offset and DST
            if (!xarVarFetch('defaulttimezone','str:1:',$defaulttimezone,'',XARVAR_NOT_REQUIRED)) return;
            if (!empty($defaulttimezone)) {
                $timezoneinfo = xarModAPIFunc('base','user','timezones',
                                              array('timezone' => $defaulttimezone));
                if (!empty($timezoneinfo)) {
                    xarConfigSetVar('Site.Core.TimeZone', $defaulttimezone);
                    list($hours,$minutes) = explode(':',$timezoneinfo[0]);
                    // tz offset is in hours
                    $offset = (float) $hours + (float) $minutes / 60;
                    xarConfigSetVar('Site.MLS.DefaultTimeOffset', $offset);
                } else {
                    // unknown/invalid timezone
                }
            } else {
                xarConfigSetVar('Site.Core.TimeZone', '');
                xarConfigSetVar('Site.MLS.DefaultTimeOffset', 0);
            }

            break;
    }

    // Call updateconfig hooks
    xarModCallHooks('module','updateconfig','base', array('module' => 'base'));

    xarResponseRedirect(xarModURL('base', 'admin', 'modifyconfig',array('tab' => $data['tab'])));

    return true;
}

?>