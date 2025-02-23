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
        $this->var()->find('phase', $phase, 'str:1:100', 'modify');
        $this->var()->find('tab', $data['tab'], 'str:1:100', 'display');
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
                        $this->var()->find('clear', $clear);
                        if (isset($clear)) {
                            unlink($filepath);
                            touch($filepath);
                        }
                        // Rename the log file and create a new, empty one
                        $this->var()->find('clearsave', $clear);
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
                        $this->var()->find('middleware', $middleware, 'str', 'Creole');
                        $variables = ['DB.Middleware' => $middleware];
                        $current_database = xarSystemVars::get(sys::CONFIG, 'DB.Name');
                        $this->var()->find('database', $database, 'str', $current_database);
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
                        $this->var()->find('alternatepagetemplate', $alternatePageTemplate, 'checkbox', false);
                        $this->var()->find('alternatepagetemplatename', $alternatePageTemplateName, 'str', '');
                        $this->var()->find('defaultmodule', $defaultModuleName, 'str:1:', xarModVars::get('modules', 'defaultmodule'));
                        $this->var()->find('defaulttype', $defaultModuleType, 'str:1:', xarModVars::get('modules', 'defaultmoduletype'));
                        $this->var()->find('defaultfunction', $defaultModuleFunction, 'str:1:', xarModVars::get('modules', 'defaultmodulefunction'));
                        $this->var()->find('defaultdatapath', $defaultDataPath, 'str:1:', xarModVars::get('modules', 'defaultdatapath'));
                        $this->var()->find('shorturl', $enableShortURLs, 'str', false);
                        $this->var()->find('allowsslashes', $allowsslashes, 'checkbox', false);
                        $this->var()->find('htmlentites', $FixHTMLEntities, 'checkbox', false);

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
                        $this->var()->find('securitylevel', $securityLevel, 'str:1:');
                        $this->var()->find('sessionduration', $sessionDuration, 'int:1:', 30);
                        $this->var()->find('sessiontimeout', $sessionTimeout, 'int:1:', 10);
                        $this->var()->find('authmodule_order', $authmodule_order, 'str:1:', '');
                        $this->var()->find('cookiename', $cookieName, 'str:1:', '');
                        $this->var()->find('cookiepath', $cookiePath, 'str:1:', '');
                        $this->var()->find('cookiedomain', $cookieDomain, 'str:1:', '');
                        $this->var()->find('referercheck', $refererCheck, 'str:1:', '');
                        $this->var()->find('secureserver', $secureServer, 'checkbox', true);
                        $this->var()->find('sslport', $sslport, 'int', 443);
                        $this->var()->find('cookietimeout', $cookietimeout, 'int:1:', '');
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
                        $this->var()->find('cipher', $cipher, 'str:1', 'blowfish');
                        $this->var()->find('mode', $mode, 'str:1', 'cbc');
                        $this->var()->find('key', $key, 'str:1', 'jamaica');
                        $this->var()->find('initvector', $initvector, 'str:1', 'xaraya2x');
                        $this->var()->find('hint', $hint, 'str:1', '');

                        $this->var()->find('key', $key, 'str:1', 'jamaica');
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
                        $this->var()->find('defaultlocale', $defaultLocale, 'str:1:');
                        $this->var()->find('mlsmode', $MLSMode, 'str:1:', 'SINGLE');

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
                        $this->var()->find('logenabled', $logenabled, 'int', 0);
                        // The loggers that can be made active
                        $data['logavailable']->checkInput('available_loggers');
                        // The log levels for the fallback logger
                        $levels = DataPropertyMaster::getProperty(['name' => 'checkboxlist']);
                        $levels->checkInput('loglevel');
                        $loglevel = serialize($levels->value);
                        // The file name for the fallback logger
                        $this->var()->find('logfilename', $logfilename, 'str', '');

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
                        $this->var()->find('loadlegacy', $loadLegacy, 'checkbox', xarConfigVars::get(null, 'Site.Core.LoadLegacy'));
                        $this->var()->find('proxyhost', $proxyhost, 'str:1:', xarModVars::get('base', 'proxyhost'));
                        $this->var()->find('proxyport', $proxyport, 'int:1:', xarModVars::get('base', 'proxyport'));
                        $this->var()->find('releasenumber', $releasenumber, 'int:1:', xarModVars::get('base', 'releasenumber'));
                        // Save these in normal module variables for now
                        xarModVars::set('base', 'proxyhost', $proxyhost);
                        xarModVars::set('base', 'proxyport', $proxyport);
                        xarModVars::set('base', 'releasenumber', $releasenumber);
                        xarConfigVars::set(null, 'Site.Core.LoadLegacy', $loadLegacy);

                        // Timezone, offset and DST
                        $this->var()->find('hosttimezone', $hosttimezone, 'str:1:', 'UTC');
                        $this->var()->find('sitetimezone', $sitetimezone, 'str:1:', 'UTC');

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
