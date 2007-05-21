<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 * @author Marcel van der Boom
 */

/**
 * Update site configuration
 *
 * @param string tab Part of the config to update
 * @return bool true on success of update
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
            xarModVars::set('themes', 'themesdirectory', $defaultThemeDir)
            if (!xarVarFetch('compilerversion','str:1:',$compilerversion,xarConfigGetVar('Site.BL.CompilerVersion'),XARVAR_NOT_REQUIRED)) return;
            xarConfigSetVar('Site.BL.CompilerVersion', $compilerversion);

            xarModVars::set('modules', 'defaultmodule', $defaultModuleName);
            xarModVars::set('modules', 'defaultmoduletype',$defaultModuleType);
            xarModVars::set('modules', 'defaultmodulefunction',$defaultModuleFunction);
            xarModVars::set('base','UseAlternatePageTemplate', ($alternatePageTemplate ? 1 : 0));
            xarModVars::set('base','AlternatePageTemplateName', $alternatePageTemplateName);

            xarModSetUserVar('roles','userhome', xarModURL($defaultModuleName, $defaultModuleType, $defaultModuleFunction),1);
            xarConfigSetVar('Site.Core.EnableShortURLsSupport', $enableShortURLs);
            // enable short urls for the base module itself too
            xarModVars::set('base','SupportShortURLs', ($enableBaseShortURLs ? 1 : 0));
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
                if (xarMLSGetCharsetFromLocale($defaultLocale) != 'utf-8') {
                    throw new ConfigurationException(null,'You should select utf-8 locale as default before selecting UNBOXED mode');
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
            if (!xarVarFetch('releasenumber','int:1:',$releasenumber,10,XARVAR_NOT_REQUIRED)) return;

            // Save these in normal module variables for now
            xarModVars::set('base','proxyhost',$proxyhost);
            xarModVars::set('base','proxyport',$proxyport);
            xarModVars::set('base','releasenumber', $releasenumber);
            xarConfigSetVar('Site.Core.LoadLegacy', $loadLegacy);
            xarModVars::set('base','editor',$editor);

            // Timezone, offset and DST
            if (!xarVarFetch('defaultsystemtimezone','str:1:',$defaultsystemtimezone,'UTC',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('defaulttimezone','str:1:',$defaulttimezone,'UTC',XARVAR_NOT_REQUIRED)) return;

            $tzobject = new DateTimezone($defaultsystemtimezone);
            if (!empty($tzobject)) {
                xarConfigSetVar('System.Core.TimeZone', $defaultsystemtimezone);
            } else {
                xarConfigSetVar('System.Core.TimeZone', "UTC");
            }
            $tzobject = new DateTimezone($defaulttimezone);
            if (!empty($tzobject)) {
                $datetime = new DateTime();
                xarConfigSetVar('Site.Core.TimeZone', $defaulttimezone);
                xarConfigSetVar('Site.MLS.DefaultTimeOffset', $tzobject->getOffset($datetime));
            } else {
                xarConfigSetVar('Site.Core.TimeZone', "UTC");
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
