<?php
/**
 * Modify site configuration
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Modify site configuration
 * @author John Robeson
 * @author Greg Allan
 * @return array of template values
 */
function base_admin_modifyconfig()
{
    // Security Check
    if(!xarSecurityCheck('AdminBase')) return;
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'display', XARVAR_NOT_REQUIRED)) return;

    $localehome = sys::varpath() . "/locales";
    if (!file_exists($localehome)) {
        throw new DirectoryNotFoundException($localehome);
    }
    $dd = opendir($localehome);
    $locales = array();
    while ($filename = readdir($dd)) {
            if (is_dir($localehome . "/" . $filename) && file_exists($localehome . "/" . $filename . "/locale.xml")) {
                $locales[] = $filename;
            }
    }
    closedir($dd);

    $data['hostdatetime'] = new DateTime();
    $tzobject = new DateTimeZone(xarConfigVars::get(null, 'System.Core.TimeZone'));
    $data['hostdatetime']->setTimezone($tzobject);

    $data['sitedatetime'] = new DateTime();
    $tzobject = new DateTimeZone(xarConfigVars::get(null, 'Site.Core.TimeZone'));
    $data['sitedatetime']->setTimezone($tzobject);

    $data['editor'] = xarModVars::get('base','editor');
    $data['editors'] = array(array('displayname' => xarML('none')));
    if(xarModIsAvailable('htmlarea')) $data['editors'][] = array('displayname' => 'htmlarea');
    if(xarModIsAvailable('fckeditor')) $data['editors'][] = array('displayname' => 'fckeditor');
    if(xarModIsAvailable('tinymce')) $data['editors'][] = array('displayname' => 'tinymce');
    $allowedlocales = xarConfigVars::get(null, 'Site.MLS.AllowedLocales');
    foreach($locales as $locale) {
        if (in_array($locale, $allowedlocales)) $active = true;
        else $active = false;
        $data['locales'][] = array('name' => $locale, 'active' => $active);
    }
    $data['releasenumber'] = xarModVars::get('base','releasenumber');

    // TODO: delete after new backend testing
    // $data['translationsBackend'] = xarConfigVars::get(null, 'Site.MLS.TranslationsBackend');
    $data['authid'] = xarSecGenAuthKey();
    $data['updatelabel'] = xarML('Update Base Configuration');
    $data['XARCORE_VERSION_NUM'] = xarCore::VERSION_NUM;
    $data['XARCORE_VERSION_ID'] =  xarCore::VERSION_ID;
    $data['XARCORE_VERSION_SUB'] = xarCore::VERSION_SUB;

    $data['module_settings'] = xarMod::apiFunc('base','admin','getmodulesettings',array('module' => 'base'));
    $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls, user_menu_link');
    $data['module_settings']->getItem();
    switch (strtolower($phase)) {
        case 'modify':
        default:
            if (!isset($phase)) {
                xarSession::setVar('statusmsg', '');
            }
            $data['inheritdeny'] = xarModVars::get('privileges', 'inheritdeny');
            break;

        case 'update':
            switch ($data['tab']) {
                case 'display':
                    if (!xarVarFetch('alternatepagetemplate','checkbox',$alternatePageTemplate,false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('alternatepagetemplatename','str',$alternatePageTemplateName,'',XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('defaultmodule',  'str:1:', $defaultModuleName, xarModVars::get('modules', 'defaultmodule'), XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('defaulttype',    'str:1:', $defaultModuleType, xarModVars::get('modules', 'defaultmoduletype'), XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('defaultfunction','str:1:', $defaultModuleFunction,xarModVars::get('modules', 'defaultmodulefunction'),XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('defaultdatapath','str:1:', $defaultDataPath, xarModVars::get('modules', 'defaultdatapath'),XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('shorturl','checkbox',$enableShortURLs,false,XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('htmlenitites','checkbox',$FixHTMLEntities,false,XARVAR_NOT_REQUIRED)) return;

                    $isvalid = $data['module_settings']->checkInput();
                    if (!$isvalid) {
                        return xarTplModule('base','admin','modifyconfig', $data);
                    } else {
                        $itemid = $data['module_settings']->updateItem();
                    }

                    xarModVars::set('modules', 'defaultmodule', $defaultModuleName);
                    xarModVars::set('modules', 'defaultmoduletype',$defaultModuleType);
                    xarModVars::set('modules', 'defaultmodulefunction',$defaultModuleFunction);
                    xarModVars::set('modules', 'defaultdatapath',$defaultDataPath);
                    xarModVars::set('base','UseAlternatePageTemplate', ($alternatePageTemplate ? 1 : 0));
                    xarModVars::set('base','AlternatePageTemplateName', $alternatePageTemplateName);

                    xarModUserVars::set('roles','userhome', xarModURL($defaultModuleName, $defaultModuleType, $defaultModuleFunction),1);
                    xarConfigVars::set(null, 'Site.Core.EnableShortURLsSupport', $enableShortURLs);
                    // enable short urls for the base module itself too
                    xarConfigVars::set(null, 'Site.Core.FixHTMLEntities', $FixHTMLEntities);
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

                    sys::import('modules.dynamicdata.class.properties.master');
                    $orderselect = DataPropertyMaster::getProperty(array('name' => 'orderselect'));
                    $orderselect->checkInput('authmodules');

                    //Filtering Options
                    // Security Levels
                    xarConfigVars::set(null, 'Site.Core.EnableSecureServer', $secureServer);
                    xarConfigVars::set(null, 'Site.Session.SecurityLevel', $securityLevel);
                    xarConfigVars::set(null, 'Site.Session.Duration', $sessionDuration);
                    xarConfigVars::set(null, 'Site.Session.InactivityTimeout', $sessionTimeout);
                    xarConfigVars::set(null, 'Site.Session.CookieName', $cookieName);
                    xarConfigVars::set(null, 'Site.Session.CookiePath', $cookiePath);
                    xarConfigVars::set(null, 'Site.Session.CookieDomain', $cookieDomain);
                    xarConfigVars::set(null, 'Site.Session.RefererCheck', $refererCheck);

                    // Authentication modules
                    if (!empty($orderselect->order)) {
                        xarConfigVars::set(null, 'Site.User.AuthenticationModules', $orderselect->order);
                    }
                    break;
                case 'locales':
                    if (!xarVarFetch('defaultlocale','str:1:',$defaultLocale)) return;
                    if (!xarVarFetch('active','array',$active, array(), XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('mlsmode','str:1:',$MLSMode,'SINGLE', XARVAR_NOT_REQUIRED)) return;

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
                    xarConfigVars::set(null, 'Site.MLS.MLSMode', $MLSMode);
                    xarConfigVars::set(null, 'Site.MLS.DefaultLocale', $defaultLocale);
                    xarConfigVars::set(null, 'Site.MLS.AllowedLocales', $localesList);

                    break;
                case 'other':
                    if (!xarVarFetch('loadlegacy', 'checkbox', $loadLegacy, true, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('proxyhost', 'str:1:', $proxyhost,'', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('proxyport','int:1:', $proxyport, 0, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('editor','str:1:',$editor,'none', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('releasenumber','int:1:', $releasenumber, xarModVars::get('base','releasenumber'),XARVAR_NOT_REQUIRED)) return;
                    // Save these in normal module variables for now
                    xarModVars::set('base','proxyhost',$proxyhost);
                    xarModVars::set('base','proxyport',$proxyport);
                    xarModVars::set('base','releasenumber', $releasenumber);
                    xarConfigVars::set(null, 'Site.Core.LoadLegacy', $loadLegacy);
                    xarModVars::set('base','editor',$editor);

                    // Timezone, offset and DST
                    if (!xarVarFetch('hosttimezone','str:1:',$hosttimezone,'UTC',XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('sitetimezone','str:1:',$sitetimezone,'UTC',XARVAR_NOT_REQUIRED)) return;

                    $tzobject = new DateTimezone($hosttimezone);
                    if (!empty($tzobject)) {
                        xarConfigVars::set(null, 'System.Core.TimeZone', $hosttimezone);
                    } else {
                        xarConfigVars::set(null, 'System.Core.TimeZone', "UTC");
                    }
                    $tzobject = new DateTimezone($sitetimezone);
                    if (!empty($tzobject)) {
                        $datetime = new DateTime();
                        xarConfigVars::set(null, 'Site.Core.TimeZone', $sitetimezone);
                        xarConfigVars::set(null, 'Site.MLS.DefaultTimeOffset', $tzobject->getOffset($datetime));
                    } else {
                        xarConfigVars::set(null, 'Site.Core.TimeZone', "UTC");
                        xarConfigVars::set(null, 'Site.MLS.DefaultTimeOffset', 0);
                    }

                    break;
            }

            // Call updateconfig hooks
            xarModCallHooks('module','updateconfig','base', array('module' => 'base'));
        }
    return $data;
}

?>
