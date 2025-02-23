<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Base\AdminGui;
use Xaraya\Modules\Base\AdminApi;
use ConfigurationException;
use DataPropertyMaster;
use DateTime;
use DateTimeZone;
use DirectoryNotFoundException;
use FilePickerProperty;
use OrderSelectProperty;
use Query;
use xarCache;
use xarConfigVars;
use xarController;
use xarCore;
use xarLog;
use xarMLS;
use xarMod;
use xarModHooks;
use xarModUserVars;
use xarModVars;
use xarSec;
use xarSecurity;
use xarSession;
use xarSystemVars;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * base admin modifyconfig function
 * @extends MethodClass<AdminGui>
 */
class ModifyconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify the configuration settings of this module
     * Standard GUI function to display and update the configuration settings of the module based on input data.
     * @author John Robeson
     * @author Greg Allan
     * @return mixed Data array for the template display or output display string if invalid data submitted
     * @see AdminGui::modifyconfig()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('AdminBase')) {
            return;
        }

        $data = [];
        xarVar::fetch('phase', 'str:1:100', $phase, 'modify', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY);
        xarVar::fetch('tab', 'str:1:100', $data['tab'], 'display', xarVar::NOT_REQUIRED);
        if (empty($data['tab'])) {
            $data['tab'] = 'display';
        }

        $localehome = sys::varpath() . "/locales";
        if (!file_exists($localehome)) {
            throw new DirectoryNotFoundException($localehome);
        }
        $dd = opendir($localehome);
        $locales = [];
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
        foreach ($locales as $locale) {
            if (in_array($locale, $data['allowedlocales'])) {
                $active = true;
            } else {
                $active = false;
            }
            $data['locales'][] = ['id' => $locale, 'name' => $locale, 'active' => $active];
        }

        $data['releasenumber'] = xarModVars::get('base', 'releasenumber');

        // TODO: delete after new backend testing
        // $data['translationsBackend'] = xarConfigVars::get(null, 'Site.MLS.TranslationsBackend');
        $data['authid'] = xarSec::genAuthKey();
        $data['updatelabel'] = xarML('Update Base Configuration');

        $data['module_settings'] = $adminapi->getmodulesettings(['module' => 'base']);
        $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls, user_menu_link');
        $data['module_settings']->getItem();

        /** @var FilePickerProperty $picker */
        $picker = DataPropertyMaster::getProperty(['name' => 'filepicker']);
        $picker->initialization_basedirectory = sys::varpath() . "/logs/";
        $picker->setExtensions('txt,html');
        $picker->display_fullname = true;
        $data['logfiles'] = $picker->getOptions();

        $data['logavailable'] = DataPropertyMaster::getProperty(['name' => 'checkboxlist']);
        $data['logavailable']->options = [
            ['id' => 'simple', 'name' => xarML('Simple')],
            ['id' => 'mail', 'name' => xarML('Mail')],
            ['id' => 'error_log', 'name' => xarML('Error Log')],
            ['id' => 'html', 'name' => xarML('HTML')],
            ['id' => 'javascript', 'name' => xarML('Javascript')],
            ['id' => 'mozilla', 'name' => xarML('Mozilla')],
            ['id' => 'sql', 'name' => xarML('SQL')],
            ['id' => 'syslog', 'name' => xarML('Syslog')],
            ['id' => 'winsyslog', 'name' => xarML('WinSyslog')],
        ];
        $data['available_loggers'] = xarLog::availables();

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
                        $nonxaraya = ['information_schema', 'performance_schema', 'sys', 'mysql'];
                        $dbs = $q->output();
                        $data['allowed_dbs'] = [];
                        foreach ($dbs as $k => $row) {
                            $db = reset($row);
                            if (in_array($db, $nonxaraya)) {
                                continue;
                            }
                            $data['allowed_dbs'][] = ['id' => $db, 'name' => $db];
                        }
                        $data['xarCoreBuild'] = xarCore::$build;
                        $data['realpaths'] = [];
                        $data['realpaths']['index.php'] = realpath('index.php');
                        $data['realpaths']['bootstrap.php'] = realpath('bootstrap.php');
                        $data['realpaths']['config.system.php'] = realpath(sys::varpath() . '/' . 'config.system.php');
                        $data['realpaths']['config.log.php'] = realpath(sys::varpath() . '/logs/' . 'config.log.php');
                        break;
                    case 'security':
                        break;
                    case 'caching':
                        $data['cache_settings'] = xarCache::getConfig();
                        if (empty($data['cache_settings']['Variable.CacheStorage'])) {
                            $data['cache_settings']['Variable.CacheStorage'] = 'apcu';
                        }
                        $cache_config_file = sys::varpath() . '/cache/config.caching.php';
                        if (file_exists($cache_config_file)) {
                            $data['cache_config_file'] = $cache_config_file;
                            $data['core_cache_sizes'] = [];
                            if (!empty($data['cache_settings']['CoreCache.Preload'])) {
                                // check if core cache file exists and save its filesize
                                foreach ($data['cache_settings']['CoreCache.Preload'] as $scope => $value) {
                                    if (str_contains($scope, ':')) {
                                        $pieces = explode(':', $scope);
                                        $filepath = sys::varpath() . '/cache/core/' . $pieces[0] . '.' . $pieces[1] . '.php';
                                        if (file_exists($filepath)) {
                                            $data['core_cache_sizes'][$scope] = filesize($filepath);
                                        }
                                    } else {
                                        $filepath = sys::varpath() . '/cache/core/' . $scope . '.php';
                                        if (file_exists($filepath)) {
                                            $data['core_cache_sizes'][$scope] = filesize($filepath);
                                        }
                                    }
                                }
                                /**
                                                    {% for scope, value in cache_settings['CoreCache.Preload'] %}
                                                        {% if '.' in scope %}
                                                            {% set pieces = scope|split('.') %}
                                                            {% set filepath = xar_coremethod('sys', 'varpath') ~ '/cache/core/' ~ pieces[0] ~ '.' ~ pieces[1] ~ '.php' %}
                                                            {% if value is null %}
                                                                Scope: {{ pieces[0] }} - Name: {{ pieces[1] }} (disabled)<br/>
                                                            {# @todo elseif file_exists(filepath) #}
                                                            {% elseif "file_exists(filepath)" %}
                                                                    Scope: {{ pieces[0] }} - Name: {{ pieces[1] }} ("filesize(filepath)"{# @todo filesize(filepath) #} bytes)<br/>
                                                            {% else %}
                                                                Scope: {{ pieces[0] }} - Name: {{ pieces[1] }} (not cached)<br/>
                                                            {% endif %}
                                                        {% else %}
                                                            {% set filepath = xar_coremethod('sys', 'varpath') ~ '/cache/core/' ~ scope ~ '.php' %}
                                                            {% if value is null %}
                                                                Scope: {{ scope }} (disabled)<br/>
                                                            {# @todo elseif file_exists(filepath) #}
                                                            {% elseif "file_exists(filepath)" %}
                                                                Scope: {{ scope }} ("filesize(filepath)"{# @todo filesize(filepath) #} bytes)<br/>
                                                            {% else %}
                                                                Scope: {{ scope }} (not cached)<br/>
                                                            {% endif %}
                                                        {% endif %}
                                                    {% endfor %}
                                 */
                            }
                        }
                        break;
                    case 'logging':
                        $filepath = $picker->initialization_basedirectory . xarSystemVars::get(sys::CONFIG, 'Log.Filename');
                        // Delete the log file and create a new, empty one
                        xarVar::fetch('clear', 'isset', $clear, null, xarVar::NOT_REQUIRED);
                        if (isset($clear)) {
                            unlink($filepath);
                            touch($filepath);
                        }
                        // Rename the log file and create a new, empty one
                        xarVar::fetch('clearsave', 'isset', $clear, null, xarVar::NOT_REQUIRED);
                        if (isset($clear)) {
                            $newname = $filepath . "_" . time();
                            rename($filepath, $newname);
                            touch($filepath);
                        }
                        if (xarSystemVars::get(sys::CONFIG, 'Log.Enabled')) {
                            $data['log_data'] = trim($adminapi->read_file(['file' => $filepath]));
                        } else {
                            $data['log_data'] = '';
                        }
                        break;
                }
                break;
            case 'update':
                switch ($data['tab']) {
                    case 'setup':
                        xarVar::fetch('middleware', 'str', $middleware, 'Creole', xarVar::NOT_REQUIRED);
                        $variables = ['DB.Middleware' => $middleware];
                        $current_database = xarSystemVars::get(sys::CONFIG, 'DB.Name');
                        xarVar::fetch('database', 'str', $database, $current_database, xarVar::NOT_REQUIRED);
                        $variables['DB.Name'] = $database;
                        xarMod::apiFunc('installer', 'admin', 'modifysystemvars', ['variables' => $variables]);
                        xarController::redirect(xarController::URL(
                            'base',
                            'admin',
                            'modifyconfig',
                            ['tab' => 'setup']
                        ), null, $this->getContext());
                        break;
                    case 'display':
                        xarVar::fetch('alternatepagetemplate', 'checkbox', $alternatePageTemplate, false, xarVar::NOT_REQUIRED);
                        xarVar::fetch('alternatepagetemplatename', 'str', $alternatePageTemplateName, '', xarVar::NOT_REQUIRED);
                        xarVar::fetch('defaultmodule', 'str:1:', $defaultModuleName, xarModVars::get('modules', 'defaultmodule'), xarVar::NOT_REQUIRED);
                        xarVar::fetch('defaulttype', 'str:1:', $defaultModuleType, xarModVars::get('modules', 'defaultmoduletype'), xarVar::NOT_REQUIRED);
                        xarVar::fetch('defaultfunction', 'str:1:', $defaultModuleFunction, xarModVars::get('modules', 'defaultmodulefunction'), xarVar::NOT_REQUIRED);
                        xarVar::fetch('defaultdatapath', 'str:1:', $defaultDataPath, xarModVars::get('modules', 'defaultdatapath'), xarVar::NOT_REQUIRED);
                        xarVar::fetch('shorturl', 'str', $enableShortURLs, false, xarVar::NOT_REQUIRED);
                        xarVar::fetch('allowsslashes', 'checkbox', $allowsslashes, false, xarVar::NOT_REQUIRED);
                        xarVar::fetch('htmlentites', 'checkbox', $FixHTMLEntities, false, xarVar::NOT_REQUIRED);

                        $isvalid = $data['module_settings']->checkInput();
                        if (!$isvalid) {
                            $data['context'] ??= $this->getContext();
                            return xarTpl::module('base', 'admin', 'modifyconfig', $data);
                        } else {
                            $itemid = $data['module_settings']->updateItem();
                        }

                        xarModVars::set('modules', 'defaultmodule', $defaultModuleName);
                        xarModVars::set('modules', 'defaultmoduletype', $defaultModuleType);
                        xarModVars::set('modules', 'defaultmodulefunction', $defaultModuleFunction);
                        xarModVars::set('modules', 'defaultdatapath', $defaultDataPath);
                        xarModVars::set('base', 'UseAlternatePageTemplate', ($alternatePageTemplate ? 1 : 0));
                        xarModVars::set('base', 'AlternatePageTemplateName', $alternatePageTemplateName);

                        xarModUserVars::set('roles', 'userhome', xarController::URL($defaultModuleName, $defaultModuleType, $defaultModuleFunction), 1);
                        xarConfigVars::set(null, 'Site.Core.EnableShortURLsSupport', $enableShortURLs);
                        xarConfigVars::set(null, 'Site.Core.WebserverAllowsSlashes', $allowsslashes);
                        // enable short urls for the base module itself too
                        xarConfigVars::set(null, 'Site.Core.FixHTMLEntities', $FixHTMLEntities);
                        break;
                    case 'security':
                        xarVar::fetch('securitylevel', 'str:1:', $securityLevel);
                        xarVar::fetch('sessionduration', 'int:1:', $sessionDuration, 30, xarVar::NOT_REQUIRED);
                        xarVar::fetch('sessiontimeout', 'int:1:', $sessionTimeout, 10, xarVar::NOT_REQUIRED);
                        xarVar::fetch('authmodule_order', 'str:1:', $authmodule_order, '', xarVar::NOT_REQUIRED);
                        xarVar::fetch('cookiename', 'str:1:', $cookieName, '', xarVar::NOT_REQUIRED);
                        xarVar::fetch('cookiepath', 'str:1:', $cookiePath, '', xarVar::NOT_REQUIRED);
                        xarVar::fetch('cookiedomain', 'str:1:', $cookieDomain, '', xarVar::NOT_REQUIRED);
                        xarVar::fetch('referercheck', 'str:1:', $refererCheck, '', xarVar::NOT_REQUIRED);
                        xarVar::fetch('secureserver', 'checkbox', $secureServer, true, xarVar::NOT_REQUIRED);
                        xarVar::fetch('sslport', 'int', $sslport, 443, xarVar::NOT_REQUIRED);
                        xarVar::fetch('cookietimeout', 'int:1:', $cookietimeout, '', xarVar::NOT_REQUIRED);
                        sys::import('modules.dynamicdata.class.properties.master');
                        /** @var OrderSelectProperty $orderselect */
                        $orderselect = DataPropertyMaster::getProperty(['name' => 'orderselect']);
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
                        xarVar::fetch('cipher','str:1',$cipher,'blowfish',xarVar::NOT_REQUIRED);
                        xarVar::fetch('mode','str:1',$mode,'cbc',xarVar::NOT_REQUIRED);
                        xarVar::fetch('key','str:1',$key,'jamaica',xarVar::NOT_REQUIRED);
                        xarVar::fetch('initvector','str:1',$initvector,'xaraya2x',xarVar::NOT_REQUIRED);
                        xarVar::fetch('hint','str:1',$hint,'',xarVar::NOT_REQUIRED);

                        xarVar::fetch('key','str:1',$key,'jamaica',xarVar::NOT_REQUIRED);
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
                        xarController::redirect(xarController::URL(
                            'base',
                            'admin',
                            'modifyconfig',
                            ['tab' => 'security']
                        ), null, $this->getContext());
                        break;
                    case 'locales':
                        xarVar::fetch('defaultlocale', 'str:1:', $defaultLocale);
                        xarVar::fetch('mlsmode', 'str:1:', $MLSMode, 'SINGLE', xarVar::NOT_REQUIRED);

                        sys::import('modules.dynamicdata.class.properties.master');
                        $locales = DataPropertyMaster::getProperty(['name' => 'checkboxlist']);
                        $locales->checkInput('active');
                        $localesList = $locales->getValue();
                        if (!in_array($defaultLocale, $localesList)) {
                            $localesList[] = $defaultLocale;
                        }
                        sort($localesList);
                        if ($MLSMode == 'UNBOXED') {
                            if (xarMLS::getCharsetFromLocale($defaultLocale) != 'utf-8') {
                                throw new ConfigurationException(null, 'You should select utf-8 locale as default before selecting UNBOXED mode');
                            }
                        }

                        // Locales
                        xarConfigVars::set(null, 'Site.MLS.MLSMode', $MLSMode);
                        xarConfigVars::set(null, 'Site.MLS.DefaultLocale', $defaultLocale);
                        xarConfigVars::set(null, 'Site.MLS.AllowedLocales', $localesList);
                        // Also set the following modvar.
                        // It sets the navigation locale for all logged in users who have not explicitly chosen one
                        xarModVars::set('roles', 'locale', $defaultLocale);

                        xarController::redirect(xarController::URL(
                            'base',
                            'admin',
                            'modifyconfig',
                            ['tab' => 'locales']
                        ), null, $this->getContext());
                        break;
                    case 'caching':
                        break;
                    case 'logging':
                        // The overall switch to enable logging
                        xarVar::fetch('logenabled', 'int', $logenabled, 0, xarVar::NOT_REQUIRED);
                        // The loggers that can be made active
                        $data['logavailable']->checkInput('available_loggers');
                        // The log levels for the fallback logger
                        $levels = DataPropertyMaster::getProperty(['name' => 'checkboxlist']);
                        $levels->checkInput('loglevel');
                        $loglevel = serialize($levels->value);
                        // The file name for the fallback logger
                        xarVar::fetch('logfilename', 'str', $logfilename, '', xarVar::NOT_REQUIRED);

                        // Update the config.system file
                        $variables = ['Log.Enabled' => $logenabled, 'Log.Available' => $data['logavailable']->value,'Log.Level' => $loglevel, 'Log.Filename' => $logfilename];
                        xarMod::apiFunc('installer', 'admin', 'modifysystemvars', ['variables' => $variables]);

                        xarController::redirect(xarController::URL(
                            'base',
                            'admin',
                            'modifyconfig',
                            ['tab' => 'logging']
                        ), null, $this->getContext());
                        break;
                    case 'other':
                        xarVar::fetch('loadlegacy', 'checkbox', $loadLegacy, xarConfigVars::get(null, 'Site.Core.LoadLegacy'), xarVar::NOT_REQUIRED);
                        xarVar::fetch('proxyhost', 'str:1:', $proxyhost, xarModVars::get('base', 'proxyhost'), xarVar::NOT_REQUIRED);
                        xarVar::fetch('proxyport', 'int:1:', $proxyport, xarModVars::get('base', 'proxyport'), xarVar::NOT_REQUIRED);
                        xarVar::fetch('releasenumber', 'int:1:', $releasenumber, xarModVars::get('base', 'releasenumber'), xarVar::NOT_REQUIRED);
                        // Save these in normal module variables for now
                        xarModVars::set('base', 'proxyhost', $proxyhost);
                        xarModVars::set('base', 'proxyport', $proxyport);
                        xarModVars::set('base', 'releasenumber', $releasenumber);
                        xarConfigVars::set(null, 'Site.Core.LoadLegacy', $loadLegacy);

                        // Timezone, offset and DST
                        xarVar::fetch('hosttimezone', 'str:1:', $hosttimezone, 'UTC', xarVar::NOT_REQUIRED);
                        xarVar::fetch('sitetimezone', 'str:1:', $sitetimezone, 'UTC', xarVar::NOT_REQUIRED);

                        $tzobject = new DateTimeZone($hosttimezone);
                        $variables = ['SystemTimeZone' => !empty($tzobject) ? $hosttimezone : 'UTC'];
                        xarMod::apiFunc('installer', 'admin', 'modifysystemvars', ['variables' => $variables]);

                        $tzobject = new DateTimeZone($sitetimezone);
                        if (!empty($tzobject)) {
                            $datetime = new DateTime();
                            xarConfigVars::set(null, 'Site.Core.TimeZone', $sitetimezone);
                            xarConfigVars::set(null, 'Site.MLS.DefaultTimeOffset', $tzobject->getOffset($datetime));
                        } else {
                            xarConfigVars::set(null, 'Site.Core.TimeZone', "UTC");
                            xarConfigVars::set(null, 'Site.MLS.DefaultTimeOffset', 0);
                        }
                        xarModVars::set('roles', 'usertimezone', xarConfigVars::get(null, 'Site.Core.TimeZone'));
                        xarController::redirect(xarController::URL(
                            'base',
                            'admin',
                            'modifyconfig',
                            ['tab' => 'other']
                        ), null, $this->getContext());
                        break;
                }
                // save to cache if enabled
                xarConfigVars::cache();

                // Call updateconfig hooks
                xarModHooks::call('module', 'updateconfig', 'base', ['module' => 'base']);
        }
        return $data;
    }
}
