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
 * {% set info = xar_modulevar(scope, name) %}
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
            new TwigFunction('xar_guifunc', $this->xar_guifunc(...), ['is_safe' => ['html']]),
            new TwigFunction('xar_apifunc', $this->xar_apifunc(...)),
            new TwigFunction('xar_moduleurl', $this->xar_moduleurl(...)),
            new TwigFunction('xar_objecturl', $this->xar_objecturl(...)),
            new TwigFunction('xar_currenturl', $this->xar_currenturl(...)),
            new TwigFunction('xar_baseurl', $this->xar_baseurl(...)),
            new TwigFunction('xar_baseuri', $this->xar_baseuri(...)),
            // we need to mark this as safe for html
            new TwigFunction('xar_imageurl', $this->xar_imageurl(...), ['is_safe' => ['html']]),
            new TwigFunction('xar_fileurl', $this->xar_fileurl(...), ['is_safe' => ['html']]),
            new TwigFunction('xar_username', $this->xar_username(...)),
            new TwigFunction('xar_uservar', $this->xar_uservar(...)),
            new TwigFunction('xar_configvar', $this->xar_configvar(...)),
            new TwigFunction('xar_modulevar', $this->xar_modulevar(...)),
            new TwigFunction('xar_moduleid', $this->xar_moduleid(...)),
            new TwigFunction('xar_moduservar', $this->xar_moduservar(...)),
            new TwigFunction('xar_moditemvar', $this->xar_moditemvar(...)),
            new TwigFunction('xar_requestvar', $this->xar_requestvar(...)),
            new TwigFunction('xar_servervar', $this->xar_servervar(...)),
            new TwigFunction('xar_sessionvar', $this->xar_sessionvar(...)),
            new TwigFunction('xar_systemvar', $this->xar_systemvar(...)),
            new TwigFunction('xar_findvar', $this->xar_findvar(...)),
            new TwigFunction('xar_var', $this->xar_var(...)),
            new TwigFunction('xar_isloggedin', $this->xar_isloggedin(...)),
            new TwigFunction('xar_userid', $this->xar_userid(...)),
            new TwigFunction('xar_modname', $this->xar_modname(...)),
            new TwigFunction('xar_modisavailable', $this->xar_modisavailable(...)),
            new TwigFunction('xar_modishooked', $this->xar_modishooked(...)),
            new TwigFunction('xar_redirect', $this->xar_redirect(...)),
            new TwigFunction('xar_request', $this->xar_request(...)),
            new TwigFunction('xar_translate', $this->xar_translate(...)),
            new TwigFunction('xar_localedate', $this->xar_localedate(...)),
            new TwigFunction('xar_formatdate', $this->xar_formatdate(...)),
            // <xar:sec mask="..." catch="false">
            new TwigFunction('xar_security_check', $this->xar_security_check(...)),
            new TwigFunction('xar_security_authkey', $this->xar_security_authkey(...)),
            // {% set infolink = attribute('xarServer', 'getObjectURL', ['workflow_tracker', 'display', {'itemid': item['id']}]) %}
            // @todo placeholder until corresponding functions have been added
            new TwigFunction('xar_coremethod', $this->xar_coremethod(...)),
        ];
    }

    public function xar_guifunc($modName, $modType = 'user', $funcName = 'main', $args = [])
    {
        // use current context
        return $this->mod()->guiFunc($modName, $modType, $funcName, $args);
    }

    public function xar_apifunc($modName, $modType = 'user', $funcName = 'main', $args = [])
    {
        // use current context
        return $this->mod()->apiFunc($modName, $modType, $funcName, $args);
    }

    public function xar_moduleurl($modName, $modType = 'user', $funcName = 'main', $args = [])
    {
        // avoid double-encoding URLs
        $generateXMLURL = false;
        return $this->ctl()->getModuleURL($modName, $modType, $funcName, $args, $generateXMLURL);
    }

    public function xar_objecturl($objectName, $methodName = 'view', $args = [])
    {
        // avoid double-encoding URLs
        $generateXMLURL = false;
        return $this->ctl()->getObjectURL($objectName, $methodName, $args, $generateXMLURL);
    }

    public function xar_currenturl($args = [], $generateXMLURL = null)
    {
        // avoid double-encoding URLs
        $generateXMLURL ??= false;
        return $this->ctl()->getCurrentURL($args, $generateXMLURL);
    }

    public function xar_baseurl()
    {
        // avoid double-encoding URLs
        return $this->ctl()->getBaseURL();
    }

    public function xar_baseuri()
    {
        // avoid double-encoding URLs
        return $this->ctl()->getBaseURI();
    }

    public function xar_imageurl($fileName, $scope = null, $package = null)
    {
        // avoid double-encoding URLs
        return $this->tpl()->getImage($fileName, $scope, $package);
    }

    public function xar_fileurl($fileName, $scope = null, $package = null)
    {
        // avoid double-encoding URLs
        return $this->tpl()->getFile($fileName, $scope, $package);
    }

    public function xar_username($userId)
    {
        return $this->user($userId)->getName();
    }

    public function xar_uservar($name = 'id', $userId = null)
    {
        return $this->user($userId)->getVar($name);
    }

    public function xar_configvar($name)
    {
        return $this->config()->getVar($name);
    }

    public function xar_modulevar($scope, $name, $value = null)
    {
        if (isset($value)) {
            return $this->mod()->setVar($name, $value, $scope);
        }
        return $this->mod()->getVar($name, $scope);
    }

    public function xar_moduleid($modName)
    {
        return $this->mod()->getRegID($modName);
    }

    public function xar_moduservar($scope, $name, $userId = null, $value = null)
    {
        if (isset($value)) {
            return $this->mod()->setUserVar($name, $value, $userId, $scope);
        }
        return $this->mod()->getUserVar($name, $userId, $scope);
    }

    public function xar_moditemvar($scope, $name, $userId = null, $value = null)
    {
        if (isset($value)) {
            return $this->mod()->setItemVar($name, $value, $userId, $scope);
        }
        return $this->mod()->getItemVar($name, $userId, $scope);
    }

    public function xar_requestvar($name)
    {
        return $this->ctl()->getRequestVar($name);
    }

    public function xar_servervar($name)
    {
        return $this->ctl()->getServerVar($name);
    }

    public function xar_sessionvar($name, $value = null)
    {
        if (isset($value)) {
            return $this->session()->setVar($name, $value);
        }
        return $this->session()->getVar($name);
    }

    public function xar_systemvar($name)
    {
        return $this->ctl()->getSystemVar($name);
    }

    /**
     * We cannot pass $variable by reference because it's set in the Twig $context,
     * so we need to return the updated value to update it in the template here
     * {% set itemid = xar_findvar('itemid', itemid, 'notempty', 1) %}
     * As an alternative, we could pass along the Twig $context and update it directly
     * @see https://stackoverflow.com/questions/59247917/twig-variables-as-references
     */
    public function xar_findvar($name, $variable, $validation = 'isset', $defaultValue = null)
    {
        $this->var()->find($name, $variable, $validation, $defaultValue);
        return $variable;
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
            'module' => $this->mod()->getVar($args['name'], $args['module']),
            'user' => $this->user($args['user'] ?? null)->getVar($args['name']),
            'config' => $this->config()->getVar($args['name']),
            'session' => $this->session()->getVar($args['name']),
            'request' => $this->ctl()->getRequestVar($args['name']),
            default => 'Unknown scope ' . $args['scope'],
        };
        if (!empty($args['prep'])) {
            return $this->var()->prep($result);
        }
        return $result;
    }

    public function xar_isloggedin()
    {
        return $this->user()->isLoggedIn();
    }

    /**
     * Get the current user id
     * @return int|false current user id or false if anonymous
     */
    public function xar_userid($context = null)
    {
        // @todo use context to get user id
        if (!$this->user()->isLoggedIn()) {
            return false;
        }
        if (isset($context)) {
            return $context->getUserId() ?? $this->session()->getUserId();
        }
        return $this->session()->getUserId();
    }

    public function xar_modname($regId = null)
    {
        return $this->mod()->getName($regId);
    }

    public function xar_modisavailable($modName)
    {
        return $this->mod()->isAvailable($modName);
    }

    public function xar_modishooked($hookModName, $callerModName, $callerItemType = null)
    {
        return $this->mod()->isHooked($hookModName, $callerModName, $callerItemType);
    }

    public function xar_redirect(string $url)
    {
        return $this->ctl()->redirect($url);
    }

    public function xar_request()
    {
        return $this->ctl()->getRequest();
    }

    public function xar_translate($rawstring, ...$args)
    {
        return $this->ml($rawstring, ...$args);
    }

    public function xar_localedate($timestamp, $dateFormat = 'medium', $timeFormat = 'short')
    {
        $date = '';
        if (!empty($dateFormat)) {
            $date .= $this->mls()->getFormattedDate($dateFormat, $timestamp) . ' ';
        }
        if (!empty($timeFormat)) {
            $date .= $this->mls()->getFormattedTime($timeFormat, $timestamp);
        }
        return $date;
    }

    public function xar_formatdate($format = null, $timestamp = null, $addoffset = true)
    {
        return $this->mls()->formatDate($format, $timestamp, $addoffset);
    }

    public function xar_security_check($mask, $catch = 0, $component = '', $instance = '', $module = '', $rolename = '', $realm = 0, $level = 0)
    {
        return xarSecurity::check($mask, $catch, $component, $instance, $module, $rolename, $realm, $level);
    }

    public function xar_security_authkey($modName = null)
    {
        return $this->sec()->genAuthKey($modName);
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
