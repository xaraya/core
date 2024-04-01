<?php
/**
 * Twig extension to use Twig template engine for output in Xaraya
 */

namespace Xaraya\Bridge\TemplateEngine;

use Twig\TwigFunction;
use xarConfigVars;
use xarController;
use xarLocale;
use xarMLS;
use xarMod;
use xarModVars;
use xarSec;
use xarSecurity;
use xarServer;
use xarSession;
use xarTpl;
use xarUser;
use xarVar;
use sys;
use Exception;

/**
 * Xaraya Core Functions
 * ```twig
 * {{ xar_guifunc(modName, modType, funcName, params) }}
 * {% set info = xar_apifunc(modName, modType, funcName, params) %}
 * {% set link = xar_moduleurl(modName, modType, funcName, params) %}
 * {{ xar_objecturl(objectName, methodName, params) }}
 * {{ xar_currenturl({...}) }}
 * {% set link = xar_baseurl) %}
 * {% set link = xar_imageurl(fileName, scope, package) %}
 * {% set link = xar_fileurl(fileName, scope, package) %}
 * {{ xar_username(userId) }} or {% set email = xar_username(userId, 'email') %}
 * {{ xar_uservar('id') }}
 * {% set info = xar_modvar(scope, name) %}
 * {{ xar_translate(text) }} or {{ xar_translate(text, arg1, arg2, ...) }}
 * {{ xar_localedate(timestamp) }}
 *
 * {# placeholder until corresponding functions have been added #}
 * {% set info = xar_coremethod(className, methodName, args) %}
 * ```
 */
class XarayaCoreExtension extends XarayaTwigExtension
{
    public function getFilters()
    {
        return [
        ];
    }

    public function getTests()
    {
        return [
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('xar_guifunc', [$this, 'xar_guifunc'], ['is_safe' => ['html']]),
            new TwigFunction('xar_apifunc', [$this, 'xar_apifunc']),
            new TwigFunction('xar_moduleurl', [$this, 'xar_moduleurl']),
            new TwigFunction('xar_objecturl', [$this, 'xar_objecturl']),
            new TwigFunction('xar_currenturl', [$this, 'xar_currenturl']),
            new TwigFunction('xar_baseurl', [$this, 'xar_baseurl']),
            new TwigFunction('xar_baseuri', [$this, 'xar_baseuri']),
            // we need to mark this as safe for html
            new TwigFunction('xar_imageurl', [$this, 'xar_imageurl'], ['is_safe' => ['html']]),
            new TwigFunction('xar_fileurl', [$this, 'xar_fileurl'], ['is_safe' => ['html']]),
            new TwigFunction('xar_username', [$this, 'xar_username']),
            new TwigFunction('xar_uservar', [$this, 'xar_uservar']),
            new TwigFunction('xar_configvar', [$this, 'xar_configvar']),
            new TwigFunction('xar_modulevar', [$this, 'xar_modulevar']),
            new TwigFunction('xar_moduleid', [$this, 'xar_moduleid']),
            new TwigFunction('xar_var', [$this, 'xar_var']),
            new TwigFunction('xar_userid', [$this, 'xar_userid']),
            new TwigFunction('xar_modname', [$this, 'xar_modname']),
            new TwigFunction('xar_request', [$this, 'xar_request']),
            new TwigFunction('xar_translate', [$this, 'xar_translate']),
            new TwigFunction('xar_localedate', [$this, 'xar_localedate']),
            // <xar:sec mask="..." catch="false">
            new TwigFunction('xar_security_check', [$this, 'xar_security_check']),
            new TwigFunction('xar_security_authkey', [$this, 'xar_security_authkey']),
            // {% set infolink = attribute('xarServer', 'getObjectURL', ['workflow_tracker', 'display', {'itemid': item['id']}]) %}
            // @todo placeholder until corresponding functions have been added
            new TwigFunction('xar_coremethod', [$this, 'xar_coremethod']),
        ];
    }

    public function xar_guifunc($modName, $modType = 'user', $funcName = 'main', $args = [])
    {
        // use current context
        return xarMod::guiFunc($modName, $modType, $funcName, $args, $this->context);
    }

    public function xar_apifunc($modName, $modType = 'user', $funcName = 'main', $args = [])
    {
        // use current context
        return xarMod::apiFunc($modName, $modType, $funcName, $args, $this->context);
    }

    public function xar_moduleurl($modName, $modType = 'user', $funcName = 'main', $args = [])
    {
        // avoid double-encoding URLs
        return xarServer::getModuleURL($modName, $modType, $funcName, $args, false);
    }

    public function xar_objecturl($objectName, $methodName = 'view', $args = [])
    {
        // avoid double-encoding URLs
        return xarServer::getObjectURL($objectName, $methodName, $args, false);
    }

    public function xar_currenturl($args = [], $generateXMLURL = null, $target = null)
    {
        // avoid double-encoding URLs
        $generateXMLURL ??= false;
        return xarServer::getCurrentURL($args, $generateXMLURL, $target);
    }

    public function xar_baseurl()
    {
        // avoid double-encoding URLs
        return xarServer::getBaseURL();
    }

    public function xar_baseuri()
    {
        // avoid double-encoding URLs
        return xarServer::getBaseURI();
    }

    public function xar_imageurl($fileName, $scope = null, $package = null)
    {
        // avoid double-encoding URLs
        return xarTpl::getImage($fileName, $scope, $package);
    }

    public function xar_fileurl($fileName, $scope = null, $package = null)
    {
        // avoid double-encoding URLs
        return xarTpl::getFile($fileName, $scope, $package);
    }

    public function xar_username($userId)
    {
        return xarUser::getVar('name', $userId);
    }

    public function xar_uservar($name = 'id', $userId = null)
    {
        return xarUser::getVar($name, $userId);
    }

    public function xar_configvar($name)
    {
        return xarConfigVars::get(null, $name);
    }

    public function xar_modulevar($scope, $name)
    {
        return xarModVars::get($scope, $name);
    }

    public function xar_moduleid($modName)
    {
        return xarMod::getRegId($modName);
    }

    /**
     * <xar:set name="checked">
     *    <xar:var scope="module" module="themes" name="var_dump"/>
     * </xar:set>
     * @todo use context where relevant
     * @deprecated 2.5.0 use specific xar_*var() function instead
     */
    public function xar_var($args = [])
    {
        // @todo not sure how this is supposed to work
        $args['scope'] ??= 'local';
        $result = match ($args['scope']) {
            'local' => $args['name'],
            'module' => xarModVars::get($args['module'], $args['name']),
            'user' => xarUser::getVar($args['name'], $args['user'] ?? null),
            'config' => xarConfigVars::get(null, $args['name']),
            'session' => xarSession::getVar($args['name']),
            'request' => xarController::getVar($args['name']),
            default => 'Unknown scope ' . $args['scope'],
        };
        if (!empty($args['prep'])) {
            return xarVar::prepForDisplay($result);
        }
        return $result;
    }

    /**
     * Get the current user id
     * @return int|false current user id or false if anonymous
     */
    public function xar_userid($context = null)
    {
        // @todo use context to get user id
        if (!xarUser::isLoggedIn()) {
            return false;
        }
        if (isset($context)) {
            return $context->getUserId() ?? xarSession::getVar('role_id');
        }
        return xarSession::getVar('role_id');
    }

    public function xar_modname($regId = null)
    {
        return xarMod::getName($regId);
    }

    public function xar_request($url = null)
    {
        return xarController::getRequest($url);
    }

    public function xar_translate($rawstring, ...$args)
    {
        return xarMLS::translate($rawstring, ...$args);
    }

    public function xar_localedate($timestamp, $dateFormat = 'medium', $timeFormat = 'short')
    {
        $date = '';
        if (!empty($dateFormat)) {
            $date .= xarLocale::getFormattedDate($dateFormat, $timestamp) . ' ';
        }
        if (!empty($timeFormat)) {
            $date .= xarLocale::getFormattedTime($timeFormat, $timestamp);
        }
        return $date;
    }

    public function xar_security_check($mask, $catch = 0, $component = '', $instance = '', $module = '', $rolename = '', $realm = 0, $level = 0)
    {
        return xarSecurity::check($mask, $catch, $component, $instance, $module, $rolename, $realm, $level);
    }

    public function xar_security_authkey($modName = null)
    {
        return xarSec::genAuthKey($modName);
    }

    /**
     * Call static method of core class with params
     * Note: also supports calling allowed function with params
     * @todo replace with specific functions or set as template variable
     */
    public function xar_coremethod($class, $method, ...$params)
    {
        if (!isset($class)) {
            // see DD test_apis
            $allowed = ['filemtime'];
            if (!in_array($method, $allowed)) {
                throw new Exception('Function ' . $method . ' is not allowed');
            }
            return $method(...$params);
        }
        $allowed = [];
        //if (!in_array($class, $allowed)) {
        //    throw new Exception('Class ' . $class . ' is not allowed');
        //}
        return $class::$method(...$params);
    }
}
