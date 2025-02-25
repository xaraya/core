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
     * @return mixed Data array for the template display or output display string if invalid data submitted or true on redirect
     * @see AdminGui::modifyconfig()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminBase')) {
            return;
        }

        $data = [];
        $this->var()->find('phase', $phase, 'str:1:100', 'modify');
        $this->var()->find('tab', $data['tab'], 'str:1:100', 'display');
        if (empty($data['tab'])) {
            $data['tab'] = 'display';
        }

        // TODO: delete after new backend testing
        // $data['translationsBackend'] = $this->config()->getVar('Site.MLS.TranslationsBackend');
        $data['authid'] = $this->sec()->genAuthKey();
        $data['updatelabel'] = $this->ml('Update Base Configuration');

        if (!isset($phase)) {
            $this->session()->setVar('statusmsg', '');
        }

        switch (strtolower($phase)) {
            case 'modify':
            default:
                $data = $this->modifyConfig($data);
                break;

            case 'update':
                return $this->updateConfig($data);
        }
        return $data;
    }

    /**
     * Summary of modifyConfig
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public function modifyConfig(array $data)
    {
        $data['inheritdeny'] = $this->mod('privileges')->getVar('inheritdeny');

        switch ($data['tab']) {
            case 'setup':
                $data = $this->modifySetup($data);
                break;
            case 'display':
                $data = $this->modifyDisplay($data);
                break;
            case 'security':
                $data = $this->modifySecurity($data);
                break;
            case 'locales':
                $data = $this->modifyLocales($data);
                break;
            case 'caching':
                $data = $this->modifyCaching($data);
                break;
            case 'logging':
                $data = $this->modifyLogging($data);
                break;
            case 'other':
                $data = $this->modifyOther($data);
                break;
        }
        return $data;
    }

    /**
     * Summary of updateConfig
     * @param array<mixed> $data
     * @throws \ConfigurationException
     * @return string|true output display string if invalid data submitted or true on redirect
     */
    public function updateConfig(array $data)
    {
        switch ($data['tab']) {
            case 'setup':
                $result = $this->updateSetup($data);
                break;
            case 'display':
                $result = $this->updateDisplay($data);
                break;
            case 'security':
                $result = $this->updateSecurity($data);
                break;
            case 'locales':
                $result = $this->updateLocales($data);
                break;
            case 'caching':
                $result = $this->updateCaching($data);
                break;
            case 'logging':
                $result = $this->updateLogging($data);
                break;
            case 'other':
                $result = $this->updateOther($data);
                break;
        }
        // output display string if invalid data submitted
        if (is_string($result)) {
            return $result;
        }
        // save to cache if enabled
        $this->config()->cache();

        // Call updateconfig hooks
        $this->mod()->callHooks('module', 'updateconfig', 'base', ['module' => 'base']);
        return true;
    }

    /**
     * Summary of modifySetup
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public function modifySetup(array $data)
    {
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
        return $data;
    }

    /**
     * Summary of updateSetup
     * @param array<mixed> $data
     * @return string|true output display string if invalid data submitted or true on redirect
     */
    public function updateSetup(array $data)
    {
        $this->var()->find('middleware', $middleware, 'str', 'Creole');
        $variables = ['DB.Middleware' => $middleware];
        $current_database = xarSystemVars::get(sys::CONFIG, 'DB.Name');
        $this->var()->find('database', $database, 'str', $current_database);
        $variables['DB.Name'] = $database;
        $this->mod()->apiFunc('installer', 'admin', 'modifysystemvars', ['variables' => $variables]);

        $this->ctl()->redirect($this->ctl()->getModuleURL(
            'base',
            'admin',
            'modifyconfig',
            ['tab' => 'setup']
        ));
        return true;
    }

    protected function getModuleSettings()
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        $settings = $adminapi->getmodulesettings(['module' => 'base']);
        $settings->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls, user_menu_link');
        $settings->getItem();
        return $settings;
    }

    /**
     * Summary of modifyDisplay
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public function modifyDisplay(array $data)
    {
        $data['module_settings'] = $this->getModuleSettings();

        return $data;
    }

    /**
     * Summary of updateDisplay
     * @param array<mixed> $data
     * @return string|true output display string if invalid data submitted or true on redirect
     */
    public function updateDisplay(array $data)
    {
        $this->var()->find('alternatepagetemplate', $alternatePageTemplate, 'checkbox', false);
        $this->var()->find('alternatepagetemplatename', $alternatePageTemplateName, 'str', '');
        $this->var()->find('defaultmodule', $defaultModuleName, 'str:1:', $this->mod('modules')->getVar('defaultmodule'));
        $this->var()->find('defaulttype', $defaultModuleType, 'str:1:', $this->mod('modules')->getVar('defaultmoduletype'));
        $this->var()->find('defaultfunction', $defaultModuleFunction, 'str:1:', $this->mod('modules')->getVar('defaultmodulefunction'));
        $this->var()->find('defaultdatapath', $defaultDataPath, 'str:1:', $this->mod('modules')->getVar('defaultdatapath'));
        $this->var()->find('shorturl', $enableShortURLs, 'str', false);
        $this->var()->find('allowsslashes', $allowsslashes, 'checkbox', false);
        $this->var()->find('htmlentites', $FixHTMLEntities, 'checkbox', false);

        $data['module_settings'] = $this->getModuleSettings();
        $isvalid = $data['module_settings']->checkInput();
        if (!$isvalid) {
            $data['context'] ??= $this->getContext();
            return $this->tpl()->module('base', 'admin', 'modifyconfig', $data);
        }
        $itemid = $data['module_settings']->updateItem();

        $this->mod('modules')->setVar('defaultmodule', $defaultModuleName);
        $this->mod('modules')->setVar('defaultmoduletype', $defaultModuleType);
        $this->mod('modules')->setVar('defaultmodulefunction', $defaultModuleFunction);
        $this->mod('modules')->setVar('defaultdatapath', $defaultDataPath);
        $this->mod()->setVar('UseAlternatePageTemplate', ($alternatePageTemplate ? 1 : 0));
        $this->mod()->setVar('AlternatePageTemplateName', $alternatePageTemplateName);

        $this->mod('roles')->setUserVar('userhome', $this->ctl()->getModuleURL($defaultModuleName, $defaultModuleType, $defaultModuleFunction), 1);
        $this->config()->setVar('Site.Core.EnableShortURLsSupport', $enableShortURLs);
        $this->config()->setVar('Site.Core.WebserverAllowsSlashes', $allowsslashes);
        // enable short urls for the base module itself too
        $this->config()->setVar('Site.Core.FixHTMLEntities', $FixHTMLEntities);

        $this->ctl()->redirect($this->ctl()->getModuleURL(
            'base',
            'admin',
            'modifyconfig',
            ['tab' => 'display']
        ));
        return true;
    }

    /**
     * Summary of modifySecurity
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public function modifySecurity(array $data)
    {
        return $data;
    }

    /**
     * Summary of updateSecurity
     * @param array<mixed> $data
     * @return string|true output display string if invalid data submitted or true on redirect
     */
    public function updateSecurity(array $data)
    {
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
        $orderselect = $this->prop()->getProperty(['name' => 'orderselect']);
        $orderselect->checkInput('authmodules');

        //Filtering Options
        // Security Levels
        $this->config()->setVar('Site.Session.SecurityLevel', $securityLevel);
        $this->config()->setVar('Site.Session.Duration', $sessionDuration);
        $this->config()->setVar('Site.Session.InactivityTimeout', $sessionTimeout);
        $this->config()->setVar('Site.Session.CookieName', $cookieName);
        $this->config()->setVar('Site.Session.CookiePath', $cookiePath);
        $this->config()->setVar('Site.Session.CookieDomain', $cookieDomain);
        $this->config()->setVar('Site.Session.RefererCheck', $refererCheck);
        $this->config()->setVar('Site.Core.EnableSecureServer', $secureServer);
        $this->config()->setVar('Site.Core.SecureServerPort', $sslport);
        $this->config()->setVar('Site.Session.CookieTimeout', $cookietimeout);

        // Authentication modules
        if (!empty($orderselect->order)) {
            $this->config()->setVar('Site.User.AuthenticationModules', $orderselect->order);
        }

        /*
        // Encryption
        $this->var()->find('cipher', $cipher, 'str:1', 'blowfish');
        $this->var()->find('mode', $mode, 'str:1', 'cbc');
        $this->var()->find('key', $key, 'str:1', 'jamaica');
        $this->var()->find('initvector', $initvector, 'str:1', 'xaraya2x');
        $this->var()->find('hint', $hint, 'str:1', '');

        $this->var()->find('key', $key, 'str:1', 'jamaica');
        $keyholder = $this->prop()->getProperty(array('type' => 'password'));
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
        $this->mod()->apiFunc('installer','admin','modifysystemvars', $args);
        */
        $this->ctl()->redirect($this->ctl()->getModuleURL(
            'base',
            'admin',
            'modifyconfig',
            ['tab' => 'security']
        ));
        return true;
    }

    /**
     * Summary of modifyLocales
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public function modifyLocales(array $data)
    {
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

        $data['allowedlocales'] = $this->config()->getVar('Site.MLS.AllowedLocales');
        foreach ($locales as $locale) {
            if (in_array($locale, $data['allowedlocales'])) {
                $active = true;
            } else {
                $active = false;
            }
            $data['locales'][] = ['id' => $locale, 'name' => $locale, 'active' => $active];
        }

        return $data;
    }

    /**
     * Summary of updateLocales
     * @param array<mixed> $data
     * @return string|true output display string if invalid data submitted or true on redirect
     */
    public function updateLocales(array $data)
    {
        $this->var()->find('defaultlocale', $defaultLocale, 'str:1:');
        $this->var()->find('mlsmode', $MLSMode, 'str:1:', 'SINGLE');

        sys::import('modules.dynamicdata.class.properties.master');
        $locales = $this->prop()->getProperty(['name' => 'checkboxlist']);
        $locales->checkInput('active');
        $localesList = $locales->getValue();
        if (!in_array($defaultLocale, $localesList)) {
            $localesList[] = $defaultLocale;
        }
        sort($localesList);
        if ($MLSMode == 'UNBOXED') {
            if ($this->mls()->getCharsetFromLocale($defaultLocale) != 'utf-8') {
                throw new ConfigurationException(null, 'You should select utf-8 locale as default before selecting UNBOXED mode');
            }
        }

        // Locales
        $this->config()->setVar('Site.MLS.MLSMode', $MLSMode);
        $this->config()->setVar('Site.MLS.DefaultLocale', $defaultLocale);
        $this->config()->setVar('Site.MLS.AllowedLocales', $localesList);
        // Also set the following modvar.
        // It sets the navigation locale for all logged in users who have not explicitly chosen one
        $this->mod('roles')->setVar('locale', $defaultLocale);

        $this->ctl()->redirect($this->ctl()->getModuleURL(
            'base',
            'admin',
            'modifyconfig',
            ['tab' => 'locales']
        ));
        return true;
    }

    /**
     * Summary of modifyCaching
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public function modifyCaching(array $data)
    {
        $data['cache_settings'] = xarCache::getConfig();
        if (empty($data['cache_settings']['Variable.CacheStorage'])) {
            $data['cache_settings']['Variable.CacheStorage'] = 'apcu';
        }
        $cache_config_file = sys::varpath() . '/cache/config.caching.php';
        if (!file_exists($cache_config_file)) {
            return $data;
        }
        $data['cache_config_file'] = $cache_config_file;
        $data['core_cache_sizes'] = [];
        if (empty($data['cache_settings']['CoreCache.Preload'])) {
            return $data;
        }
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
        return $data;
    }

    /**
     * Summary of updateCaching
     * @param array<mixed> $data
     * @return string|true output display string if invalid data submitted or true on redirect
     */
    public function updateCaching(array $data)
    {
        // @todo

        $this->ctl()->redirect($this->ctl()->getModuleURL(
            'base',
            'admin',
            'modifyconfig',
            ['tab' => 'caching']
        ));
        return true;
    }

    /**
     * Summary of getLogFilePicker
     * @return FilePickerProperty
     */
    protected function getLogFilePicker()
    {
        /** @var FilePickerProperty $picker */
        $picker = $this->prop()->getProperty(['name' => 'filepicker']);
        $picker->initialization_basedirectory = sys::varpath() . "/logs/";
        $picker->setExtensions('txt,html');
        $picker->display_fullname = true;
        return $picker;
    }

    /**
     * Summary of getLogAvailable
     * @return \DataProperty
     */
    protected function getLogAvailable()
    {
        $logAvailable = $this->prop()->getProperty(['name' => 'checkboxlist']);
        $logAvailable->options = [
            ['id' => 'simple', 'name' => $this->ml('Simple')],
            ['id' => 'mail', 'name' => $this->ml('Mail')],
            ['id' => 'error_log', 'name' => $this->ml('Error Log')],
            ['id' => 'html', 'name' => $this->ml('HTML')],
            ['id' => 'javascript', 'name' => $this->ml('Javascript')],
            ['id' => 'mozilla', 'name' => $this->ml('Mozilla')],
            ['id' => 'sql', 'name' => $this->ml('SQL')],
            ['id' => 'syslog', 'name' => $this->ml('Syslog')],
            ['id' => 'winsyslog', 'name' => $this->ml('WinSyslog')],
        ];
        return $logAvailable;
    }

    /**
     * Summary of modifyLogging
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public function modifyLogging(array $data)
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        $picker = $this->getLogFilePicker();
        $data['logfiles'] = $picker->getOptions();

        $data['logavailable'] = $this->getLogAvailable();
        $data['available_loggers'] = xarLog::availables();

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
        return $data;
    }

    /**
     * Summary of updateLogging
     * @param array<mixed> $data
     * @return string|true output display string if invalid data submitted or true on redirect
     */
    public function updateLogging(array $data)
    {
        // The overall switch to enable logging
        $this->var()->find('logenabled', $logenabled, 'int', 0);
        // The loggers that can be made active
        $data['logavailable'] = $this->getLogAvailable();
        $data['logavailable']->checkInput('available_loggers');
        // The log levels for the fallback logger
        $levels = $this->prop()->getProperty(['name' => 'checkboxlist']);
        $levels->checkInput('loglevel');
        $loglevel = serialize($levels->value);
        // The file name for the fallback logger
        $this->var()->find('logfilename', $logfilename, 'str', '');

        // Update the config.system file
        $variables = ['Log.Enabled' => $logenabled, 'Log.Available' => $data['logavailable']->value,'Log.Level' => $loglevel, 'Log.Filename' => $logfilename];
        $this->mod()->apiFunc('installer', 'admin', 'modifysystemvars', ['variables' => $variables]);

        $this->ctl()->redirect($this->ctl()->getModuleURL(
            'base',
            'admin',
            'modifyconfig',
            ['tab' => 'logging']
        ));
        return true;
    }

    /**
     * Summary of modifyOther
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public function modifyOther(array $data)
    {
        $data['hostdatetime'] = new DateTime();
        $tzobject = new DateTimeZone(xarSystemVars::get(sys::CONFIG, 'SystemTimeZone'));
        $data['hostdatetime']->setTimezone($tzobject);

        $data['sitedatetime'] = new DateTime();
        $tzobject = new DateTimeZone($this->config()->getVar('Site.Core.TimeZone'));
        $data['sitedatetime']->setTimezone($tzobject);

        $data['releasenumber'] = $this->mod()->getVar('releasenumber');

        return $data;
    }

    /**
     * Summary of updateOther
     * @param array<mixed> $data
     * @return string|true output display string if invalid data submitted or true on redirect
     */
    public function updateOther(array $data)
    {
        $this->var()->find('loadlegacy', $loadLegacy, 'checkbox', $this->config()->getVar('Site.Core.LoadLegacy'));
        $this->var()->find('proxyhost', $proxyhost, 'str:1:', $this->mod()->getVar('proxyhost'));
        $this->var()->find('proxyport', $proxyport, 'int:1:', $this->mod()->getVar('proxyport'));
        $this->var()->find('releasenumber', $releasenumber, 'int:1:', $this->mod()->getVar('releasenumber'));
        // Save these in normal module variables for now
        $this->mod()->setVar('proxyhost', $proxyhost);
        $this->mod()->setVar('proxyport', $proxyport);
        $this->mod()->setVar('releasenumber', $releasenumber);
        $this->config()->setVar('Site.Core.LoadLegacy', $loadLegacy);

        // Timezone, offset and DST
        $this->var()->find('hosttimezone', $hosttimezone, 'str:1:', 'UTC');
        $this->var()->find('sitetimezone', $sitetimezone, 'str:1:', 'UTC');

        $tzobject = new DateTimeZone($hosttimezone);
        $variables = ['SystemTimeZone' => !empty($tzobject) ? $hosttimezone : 'UTC'];
        $this->mod()->apiFunc('installer', 'admin', 'modifysystemvars', ['variables' => $variables]);

        $tzobject = new DateTimeZone($sitetimezone);
        if (!empty($tzobject)) {
            $datetime = new DateTime();
            $this->config()->setVar('Site.Core.TimeZone', $sitetimezone);
            $this->config()->setVar('Site.MLS.DefaultTimeOffset', $tzobject->getOffset($datetime));
        } else {
            $this->config()->setVar('Site.Core.TimeZone', "UTC");
            $this->config()->setVar('Site.MLS.DefaultTimeOffset', 0);
        }
        $this->mod('roles')->setVar('usertimezone', $this->config()->getVar('Site.Core.TimeZone'));
        $this->ctl()->redirect($this->ctl()->getModuleURL(
            'base',
            'admin',
            'modifyconfig',
            ['tab' => 'other']
        ));
        return true;
    }
}
