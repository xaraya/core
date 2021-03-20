<?php
/**
 * Modify the configuration settings of this module
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */

/**
 * Modify the configuration settings of this module
 *
 * Standard GUI function to display and update the configuration settings of the module based on input data.
 *
 * @author John Robeson
 * @author Greg Allan
 * 
 * @param void N/A
 * @return mixed Data array for the template display or output display string if invalid data submitted
 */
function base_admin_modifyconfig()
{
    // Security
    if(!xarSecurity::check('AdminBase')) return;
    
    if (!xarVar::fetch('phase', 'str:1:100', $phase, 'modify', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY)) return;
    if (!xarVar::fetch('tab', 'str:1:100', $data['tab'], 'display', xarVar::NOT_REQUIRED)) return;

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

    $data['allowedlocales'] = xarConfigVars::get(null, 'Site.MLS.AllowedLocales');
    foreach($locales as $locale) {
        if (in_array($locale, $data['allowedlocales'])) $active = true;
        else $active = false;
        $data['locales'][] = array('id' => $locale, 'name' => $locale, 'active' => $active);
    }
   
    $data['releasenumber'] = xarModVars::get('base','releasenumber');

    // TODO: delete after new backend testing
    // $data['translationsBackend'] = xarConfigVars::get(null, 'Site.MLS.TranslationsBackend');
    $data['authid'] = xarSec::genAuthKey();
    $data['updatelabel'] = xarML('Update Base Configuration');

    $data['module_settings'] = xarMod::apiFunc('base','admin','getmodulesettings',array('module' => 'base'));
    $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls, user_menu_link');
    $data['module_settings']->getItem();

    if (extension_loaded('mcrypt') && 0) {
        // Don't use sys::import, the scope of the var would be wrong
        // Use include instead of include_once, in case we have loaded this var in another scope
        include(sys::lib()."xaraya/encryption.php");
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

    sys::import('modules.dynamicdata.class.properties.master');
    $combobox = DataPropertyMaster::getProperty(array('name' => 'combobox'));
    $combobox->checkInput('logfilename');
    $data['logfilename'] = !empty($combobox->value) ? $combobox->value : xarSystemVars::get(sys::CONFIG, 'Log.Filename');

    $picker = DataPropertyMaster::getProperty(array('name' => 'filepicker'));
    $picker->initialization_basedirectory = sys::varpath() . "/logs/";
    $picker->setExtensions('txt,html');
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
                case 'setup':
                $q = new Query();
                $q->setstatement('select schema_name from information_schema.schemata');
                $q->run('select schema_name from information_schema.schemata');
                $nonxaraya = array('information_schema', 'performance_schema', 'sys', 'mysql');
                $dbs = $q->output();
                $data['allowed_dbs'] = array();
                foreach ($dbs as $k => $row) {
                	$db = reset($row);
                	if (in_array($db, $nonxaraya)) continue;
                	$data['allowed_dbs'][] = array('id' => $db, 'name' => $db);
                }
                break;
                case 'security':
                break;
                case 'caching':
                    $data['cache_settings'] = xarCache::getConfig();
                    if (empty($data['cache_settings']['Variable.CacheStorage']))
                        $data['cache_settings']['Variable.CacheStorage'] = 'database';
                break;
                case 'logging':
                    // Delete the log file and create a new, empty one
                    if (!xarVar::fetch('clear','isset',$clear,NULL,xarVar::NOT_REQUIRED)) return;
                    $filepath = $picker->initialization_basedirectory . $data['logfilename'];
                    if (isset($clear)) {
                        unlink($filepath);
                        touch($filepath);
                    }
                    // Rename the log file and create a new, empty one
                    if (!xarVar::fetch('clearsave','isset',$clear,NULL,xarVar::NOT_REQUIRED)) return;
                    $filepath = $picker->initialization_basedirectory . $data['logfilename'];
                    if (isset($clear)) {
                        $newname = $filepath . "_" . time();
                        rename($filepath, $newname);
                        touch($filepath);
                    }
                    if (xarSystemVars::get(sys::CONFIG, 'Log.Enabled')) {
                        $data['log_data'] = trim(xarMod::apiFunc('base', 'admin', 'read_file', array('file' => $filepath)));
                    } else {
                        $data['log_data'] = '';
                    }
                break;
            }
            break;
        case 'update':
            switch ($data['tab']) {
                case 'setup':
                    if (!xarVar::fetch('middleware', 'str', $middleware, 'Creole' ,xarVar::NOT_REQUIRED)) return;
                    $variables = array('DB.Middleware' => $middleware);
                    $current_database = xarSystemVars::get(sys::CONFIG, 'DB.Name');
                    if (!xarVar::fetch('database', 'str', $database, $current_database ,xarVar::NOT_REQUIRED)) return;
                    $variables['DB.Name'] = $database;                    xarMod::apiFunc('installer','admin','modifysystemvars', array('variables'=> $variables));
                    xarController::redirect(xarController::URL('base', 'admin', 'modifyconfig', array('tab' => 'setup')));
                    break;
                case 'display':
                    if (!xarVar::fetch('alternatepagetemplate','checkbox',$alternatePageTemplate,false, xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('alternatepagetemplatename','str',$alternatePageTemplateName,'',xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('defaultmodule',  'str:1:', $defaultModuleName, xarModVars::get('modules', 'defaultmodule'), xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('defaulttype',    'str:1:', $defaultModuleType, xarModVars::get('modules', 'defaultmoduletype'), xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('defaultfunction','str:1:', $defaultModuleFunction,xarModVars::get('modules', 'defaultmodulefunction'),xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('defaultdatapath','str:1:', $defaultDataPath, xarModVars::get('modules', 'defaultdatapath'),xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('shorturl','str',$enableShortURLs,false,xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('allowsslashes','checkbox',$allowsslashes,false,xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('htmlentites','checkbox',$FixHTMLEntities,false,xarVar::NOT_REQUIRED)) return;

                    $isvalid = $data['module_settings']->checkInput();
                    if (!$isvalid) {
                        return xarTpl::module('base','admin','modifyconfig', $data);
                    } else {
                        $itemid = $data['module_settings']->updateItem();
                    }

                    xarModVars::set('modules', 'defaultmodule', $defaultModuleName);
                    xarModVars::set('modules', 'defaultmoduletype',$defaultModuleType);
                    xarModVars::set('modules', 'defaultmodulefunction',$defaultModuleFunction);
                    xarModVars::set('modules', 'defaultdatapath',$defaultDataPath);
                    xarModVars::set('base','UseAlternatePageTemplate', ($alternatePageTemplate ? 1 : 0));
                    xarModVars::set('base','AlternatePageTemplateName', $alternatePageTemplateName);

                    xarModUserVars::set('roles','userhome', xarController::URL($defaultModuleName, $defaultModuleType, $defaultModuleFunction),1);
                    xarConfigVars::set(null, 'Site.Core.EnableShortURLsSupport', $enableShortURLs);
                    xarConfigVars::set(null, 'Site.Core.WebserverAllowsSlashes', $allowsslashes);
                    // enable short urls for the base module itself too
                    xarConfigVars::set(null, 'Site.Core.FixHTMLEntities', $FixHTMLEntities);
                    break;
                case 'security':
                    if (!xarVar::fetch('securitylevel','str:1:',$securityLevel)) return;
                    if (!xarVar::fetch('sessionduration','int:1:',$sessionDuration,30,xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('sessiontimeout','int:1:',$sessionTimeout,10,xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('authmodule_order','str:1:',$authmodule_order,'',xarVar::NOT_REQUIRED)) {return;}
                    if (!xarVar::fetch('cookiename','str:1:',$cookieName,'',xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('cookiepath','str:1:',$cookiePath,'',xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('cookiedomain','str:1:',$cookieDomain,'',xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('referercheck','str:1:',$refererCheck,'',xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('secureserver','checkbox',$secureServer,true,xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('sslport','int',$sslport,443,xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('cookietimeout','int:1:',$cookietimeout,'',xarVar::NOT_REQUIRED)) return;
                    sys::import('modules.dynamicdata.class.properties.master');
                    $orderselect = DataPropertyMaster::getProperty(array('name' => 'orderselect'));
                    $orderselect->checkInput('authmodules');

                    //Filtering Options
                    // Security Levels
                    xarConfigVars::set(null, 'Site.Session.SecurityLevel', $securityLevel);
                    xarConfigVars::set(null, 'Site.Session.Duration', $sessionDuration);
                    xarConfigVars::set(null, 'Site.Session.InactivityTimeout', $sessionTimeout);
                    xarConfigVars::set(null, 'Site.Session.CookieName', $cookieName);
                    xarConfigVars::set(null, 'Site.Session.CookiePath', $cookiePath);
                    xarConfigVars::set(null, 'Site.Session.CookieDomain', $cookieDomain);
                    xarConfigVars::set(null, 'Site.Session.RefererCheck', $refererCheck);
                    xarConfigVars::set(null, 'Site.Core.EnableSecureServer', $secureServer);
                    xarConfigVars::set(null, 'Site.Core.SecureServerPort', $sslport);
                    xarConfigVars::set(null, 'Site.Session.CookieTimeout', $cookietimeout);

                    // Authentication modules
                    if (!empty($orderselect->order)) {
                        xarConfigVars::set(null, 'Site.User.AuthenticationModules', $orderselect->order);
                    }
                    
                    /*
                    // Encryption
                    if (!xarVar::fetch('cipher','str:1',$cipher,'blowfish',xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('mode','str:1',$mode,'cbc',xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('key','str:1',$key,'jamaica',xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('initvector','str:1',$initvector,'xaraya2x',xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('hint','str:1',$hint,'',xarVar::NOT_REQUIRED)) return;

                    if (!xarVar::fetch('key','str:1',$key,'jamaica',xarVar::NOT_REQUIRED)) return;
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
                    */
                    xarController::redirect(xarController::URL('base', 'admin', 'modifyconfig', array('tab' => 'security')));
                    break;
                case 'locales':
                    if (!xarVar::fetch('defaultlocale','str:1:',$defaultLocale)) return;
                    if (!xarVar::fetch('mlsmode','str:1:',$MLSMode,'SINGLE', xarVar::NOT_REQUIRED)) return;

                    sys::import('modules.dynamicdata.class.properties.master');
                    $locales = DataPropertyMaster::getProperty(array('name' => 'checkboxlist'));
                    $locales->checkInput('active');
                    $localesList = $locales->getValue();
                    if (!in_array($defaultLocale,$localesList)) $localesList[] = $defaultLocale;
                    sort($localesList);
                    if ($MLSMode == 'UNBOXED') {
                        if (xarMLS::getCharsetFromLocale($defaultLocale) != 'utf-8') {
                            throw new ConfigurationException(null,'You should select utf-8 locale as default before selecting UNBOXED mode');
                        }
                    }

                    // Locales
                    xarConfigVars::set(null, 'Site.MLS.MLSMode', $MLSMode);
                    xarConfigVars::set(null, 'Site.MLS.DefaultLocale', $defaultLocale);
                    xarConfigVars::set(null, 'Site.MLS.AllowedLocales', $localesList);
                    // Also set the following modvar. 
                    // It sets the navigation locale for all logged in users who have not explicitly chosen one
                    xarModVars::set('roles', 'locale', $defaultLocale);

                    xarController::redirect(xarController::URL('base', 'admin', 'modifyconfig', array('tab' => 'locales')));
                    break;
                case 'caching':                    
                    break;
                case 'logging':                    
                    if (!xarVar::fetch('logenabled','int',$logenabled,0,xarVar::NOT_REQUIRED)) return;
                    $checkboxlist = DataPropertyMaster::getProperty(array('name' => 'checkboxlist'));
                    $checkboxlist->checkInput('loglevel');
                    $loglevel = serialize($checkboxlist->value);
                    $variables = array('Log.Enabled' => $logenabled, 'Log.Level' => $loglevel, 'Log.Filename' => $data['logfilename']);
                    xarMod::apiFunc('installer','admin','modifysystemvars', array('variables'=> $variables));
                    xarController::redirect(xarController::URL('base', 'admin', 'modifyconfig', array('tab' => 'logging')));
                    break;
                case 'other':
                    if (!xarVar::fetch('loadlegacy',   'checkbox', $loadLegacy,    xarConfigVars::get(null, 'Site.Core.LoadLegacy'), xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('proxyhost',    'str:1:',   $proxyhost,     xarModVars::get('base', 'proxyhost'), xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('proxyport',    'int:1:',   $proxyport,     xarModVars::get('base', 'proxyport'), xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('releasenumber','int:1:',   $releasenumber, xarModVars::get('base','releasenumber'),xarVar::NOT_REQUIRED)) return;
                    // Save these in normal module variables for now
                    xarModVars::set('base','proxyhost',$proxyhost);
                    xarModVars::set('base','proxyport',$proxyport);
                    xarModVars::set('base','releasenumber', $releasenumber);
                    xarConfigVars::set(null, 'Site.Core.LoadLegacy', $loadLegacy);

                    // Timezone, offset and DST
                    if (!xarVar::fetch('hosttimezone','str:1:',$hosttimezone,'UTC',xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('sitetimezone','str:1:',$sitetimezone,'UTC',xarVar::NOT_REQUIRED)) return;

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
                    xarModVars::set('roles', 'usertimezone', xarConfigVars::get(null, 'Site.Core.TimeZone'));
                    xarController::redirect(xarController::URL('base', 'admin', 'modifyconfig', array('tab' => 'other')));
                    break;
            }

            // Call updateconfig hooks
            xarModHooks::call('module','updateconfig','base', array('module' => 'base'));
        }
    return $data;
}

?>
