<?php
/**
 * Modify the configuration settings of this module
 *
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Modify the configuration settings of this module
 *
 * Standard GUI function to display and update the configuration settings of the module based on input data.
 *
 * @author John Robeson
 * @author Greg Allan
 * @return mixed data array for the template display or output display string if invalid data submitted
 */
function base_admin_modifyconfig()
{
    // Security
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
    $tzobject = new DateTimeZone(xarSystemVars::get(sys::CONFIG, 'SystemTimeZone'));
    $data['hostdatetime']->setTimezone($tzobject);

    $data['sitedatetime'] = new DateTime();
    $tzobject = new DateTimeZone(xarConfigVars::get(null, 'Site.Core.TimeZone'));
    $data['sitedatetime']->setTimezone($tzobject);

    /*
    $data['editor'] = xarModVars::get('base','editor');
    $data['editors'] = array(array('displayname' => xarML('none')));
    if(xarModIsAvailable('htmlarea')) $data['editors'][] = array('displayname' => 'htmlarea');
    if(xarModIsAvailable('fckeditor')) $data['editors'][] = array('displayname' => 'fckeditor');
    if(xarModIsAvailable('tinymce')) $data['editors'][] = array('displayname' => 'tinymce');
    */
    
    $data['allowedlocales'] = xarConfigVars::get(null, 'Site.MLS.AllowedLocales');
    foreach($locales as $locale) {
        if (in_array($locale, $data['allowedlocales'])) $active = true;
        else $active = false;
        $data['locales'][] = array('id' => $locale, 'name' => $locale, 'active' => $active);
    }
   
    $data['releasenumber'] = xarModVars::get('base','releasenumber');

    // TODO: delete after new backend testing
    // $data['translationsBackend'] = xarConfigVars::get(null, 'Site.MLS.TranslationsBackend');
    $data['authid'] = xarSecGenAuthKey();
    $data['updatelabel'] = xarML('Update Base Configuration');

    $data['module_settings'] = xarMod::apiFunc('base','admin','getmodulesettings',array('module' => 'base'));
    $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls, user_menu_link');
    $data['module_settings']->getItem();

    if (extension_loaded('mcrypt')) {
        include_once(sys::lib()."xaraya/encryption.php");
        $data['encryption'] = $encryption;

        $ciphers = array();
        $ciphermenu = mcrypt_list_algorithms();
        sort($ciphermenu);
        foreach ($ciphermenu as $item)
            $ciphers[] = array('id' => $item, 'name' => $item);
        $data['ciphers'] = $ciphers;

        $modes = array();
        $modemenu = mcrypt_list_modes();
        sort($modemenu);
        foreach ($modemenu as $item)
            $modes[] = array('id' => $item, 'name' => $item);
        $data['modes'] = $modes;
    }

    if (!xarVarFetch('logfilename','str:1',$data['logfilename'], xarConfigVars::get(null, 'Site.Core.LogFilename'),XARVAR_NOT_REQUIRED)) return;
    sys::import('modules.dynamicdata.class.properties.master');
    $picker = DataPropertyMaster::getProperty(array('name' => 'filepicker'));
    $picker->initialization_basedirectory = sys::varpath() . "/logs/";
    $picker->display_fullname = true;
    $data['logfiles'] = $picker->getOptions();

    switch (strtolower($phase)) {
        case 'modify':
        default:
            if (!isset($phase)) {
                xarSession::setVar('statusmsg', '');
            }
            $data['inheritdeny'] = xarModVars::get('privileges', 'inheritdeny');

            switch ($data['tab']) {
                case 'security':
                break;
            }
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
                    
                    // Encryption
                    if (!xarVarFetch('cipher','str:1',$cipher,'blowfish',XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('mode','str:1',$mode,'cbc',XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('key','str:1',$key,'jamaica',XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('initvector','str:1',$initvector,'xaraya2x',XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('hint','str:1',$hint,'',XARVAR_NOT_REQUIRED)) return;

                    if (!xarVarFetch('key','str:1',$key,'jamaica',XARVAR_NOT_REQUIRED)) return;
                    $keyholder = DataPropertyMaster::getProperty(array('type' => 'password'));
                    $keyholder->checkInput('key',$key);
                    $key = $keyholder->value;

                    $args['filepath'] = sys::lib()."xaraya/encryption.php";
                    $args['variables'] = array(
                        'cipher' => $cipher,
                        'mode' => $mode,
                        'key' => $key,
                        'hint' => $hint,
                        'initvector' => $initvector,
                    );
                    xarMod::apiFunc('installer','admin','modifysystemvars', $args);
                    xarController::redirect(xarModURL('base', 'admin', 'modifyconfig', array('tab' => 'security')));
                    break;
                case 'locales':
                    if (!xarVarFetch('defaultlocale','str:1:',$defaultLocale)) return;
                    if (!xarVarFetch('mlsmode','str:1:',$MLSMode,'SINGLE', XARVAR_NOT_REQUIRED)) return;

                    sys::import('modules.dynamicdata.class.properties.master');
                    $locales = DataPropertyMaster::getProperty(array('name' => 'checkboxlist'));
                    $locales->checkInput('active');
                    $localesList = $locales->getValue();
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

                    xarController::redirect(xarModURL('base', 'admin', 'modifyconfig', array('tab' => 'locales')));
                    break;
                case 'logging':
                    if (!xarVarFetch('enablelogging','int',$enablelogging,0,XARVAR_NOT_REQUIRED)) return;
                    xarConfigVars::set(null, 'Site.Core.EnableLogging', $enablelogging);
                    xarConfigVars::set(null, 'Site.Core.LogFilename', $data['logfilename']);
                    break;
                case 'other':
                    if (!xarVarFetch('loadlegacy',   'checkbox', $loadLegacy,    xarConfigVars::get(null, 'Site.Core.LoadLegacy'), XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('proxyhost',    'str:1:',   $proxyhost,     xarModVars::get('base', 'proxyhost'), XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('proxyport',    'int:1:',   $proxyport,     xarModVars::get('base', 'proxyport'), XARVAR_NOT_REQUIRED)) return;
//                    if (!xarVarFetch('editor',       'str:1:',   $editor,        xarModVars::get('base', 'editor'), XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('releasenumber','int:1:',   $releasenumber, xarModVars::get('base','releasenumber'),XARVAR_NOT_REQUIRED)) return;
                    // Save these in normal module variables for now
                    xarModVars::set('base','proxyhost',$proxyhost);
                    xarModVars::set('base','proxyport',$proxyport);
                    xarModVars::set('base','releasenumber', $releasenumber);
                    xarConfigVars::set(null, 'Site.Core.LoadLegacy', $loadLegacy);
//                    xarModVars::set('base','editor',$editor);

                    // Timezone, offset and DST
                    if (!xarVarFetch('hosttimezone','str:1:',$hosttimezone,'UTC',XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('sitetimezone','str:1:',$sitetimezone,'UTC',XARVAR_NOT_REQUIRED)) return;

                    $tzobject = new DateTimezone($hosttimezone);
                    $variables = array('SystemTimeZone' => !empty($tzobject) ? $hosttimezone : 'UTC');
                    xarMod::apiFunc('installer','admin','modifysystemvars', array('variables'=> $variables));
                    
                    $tzobject = new DateTimezone($sitetimezone);
                    if (!empty($tzobject)) {
                        $datetime = new DateTime();
                        xarConfigVars::set(null, 'Site.Core.TimeZone', $sitetimezone);
                        xarConfigVars::set(null, 'Site.MLS.DefaultTimeOffset', $tzobject->getOffset($datetime));
                    } else {
                        xarConfigVars::set(null, 'Site.Core.TimeZone', "UTC");
                        xarConfigVars::set(null, 'Site.MLS.DefaultTimeOffset', 0);
                    }
                    xarController::redirect(xarModURL('base', 'admin', 'modifyconfig', array('tab' => 'other')));
                    break;
            }

            // Call updateconfig hooks
            xarModCallHooks('module','updateconfig','base', array('module' => 'base'));
        }
    return $data;
}

?>
