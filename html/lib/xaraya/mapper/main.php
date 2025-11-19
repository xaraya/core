<?php

/**
 * Main Controller class
 *
 * @package core\controllers
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

use Xaraya\Services\ControllerService;
use Xaraya\Services\RequestService;
use Xaraya\Services\xar;

/**
 * @deprecated 2.8.4 use xar::ctl() or xar::req() instead
 */
class xarController extends xarObject
{
    protected static ?ControllerService $ctlService = null;
    protected static ?RequestService $reqService = null;

    protected static function ctl(): ControllerService
    {
        if (!isset(self::$ctlService)) {
            $xar = xar::getServicesClass();
            self::$ctlService = $xar->ctl();
            self::$reqService = $xar->req();
        }
        return self::$ctlService;
    }

    protected static function req(): RequestService
    {
        if (!isset(self::$reqService)) {
            $xar = xar::getServicesClass();
            self::$ctlService = $xar->ctl();
            self::$reqService = $xar->req();
        }
        return self::$reqService;
    }

    /**
     * Initialize
     *
     * @param array<string, mixed> $args
     */
    public static function init(array $args = []): bool
    {
        // static cache for migration
        self::$ctlService = null;
        self::$reqService = null;
        return self::ctl()->init($args);
    }

    /**
     * Summary of getConfig
     * @return array<string, mixed>
     */
    public static function getConfig()
    {
        return self::ctl()->getConfig();
    }

    /**
     * Handle multi-dimensional array lookup name[key1][key2][...]
     * @param mixed $var
     * @param mixed $name
     * @return mixed
     */
    public static function getArrayVar($var, $name)
    {
        return self::req()->getArrayVar($var, $name);
    }

    /**
     * Get a request variable
     *
     * @param string $name
     * @param ?string $allowOnlyMethod
     * @return mixed
     * @todo change order (POST normally overrides GET)
     * @todo have a look at raw post data options (xmlhttp postings)
     */
    public static function getVar($name, $allowOnlyMethod = null)
    {
        return self::req()->getVar($name, $allowOnlyMethod);
    }

    /**
     * Summary of __stripslashes
     * @param array<string, mixed>|string $value
     * @return array<string, mixed>|string
     * @deprecated 2.4.1 not used
     */
    protected static function __stripslashes($value)
    {
        return self::req()->stripVarSlashes($value);
    }

    /**
     * Summary of setRequest
     * @param mixed $url
     * @return void
     */
    public static function setRequest($url = null)
    {
        self::req()->setRequest($url);
    }

    /**
     * Summary of getRequest
     * @param mixed $url
     * @return xarRequest
     */
    public static function getRequest($url = null)
    {
        return self::req()->getRequest($url);
    }

    /**
     * Summary of setResponse
     * @param ?xarResponse $response
     * @return void
     */
    public static function setResponse($response = null)
    {
        self::ctl()->setResponse($response);
    }

    /**
     * Summary of getResponse
     * @return xarResponse
     */
    public static function getResponse()
    {
        return self::ctl()->getResponse();
    }

    /**
     * Find the route for this request
     * @param xarRequest|null $request
     * @return xarRequest
     */
    public static function normalizeRequest($request = null)
    {
        return self::ctl()->normalizeRequest($request);
    }

    /**
     * Dispatch the request to the controller for that route
     * @param xarRequest|null $request
     * @return xarResponse
     */
    public static function dispatch($request = null)
    {
        return self::ctl()->dispatch($request);
    }

    /**
     * Check to see if this is a local referral
     *
     * @return boolean true if locally referred, false if not
     */
    public static function isLocalReferer()
    {
        return self::req()->isLocalReferer();
    }

    /**
     * Check if the referral comes from the same module for admin overview
     * @return bool
     */
    public static function isRefererSameModule()
    {
        return self::req()->isSameReferer();
    }

    /**
     * Carry out a redirect
     * with context and callback
     *
     * @param string $url the URL to redirect to
     * @param mixed $httpResponse
     * @param mixed $context
     * @return bool|never
     */
    public static function redirect($url, $httpResponse = null, $context = null)
    {
        return self::ctl()->redirect($url, $httpResponse);
    }

    /**
     * Return a 403 Forbidden header, and fill in the message-forbidden.xt template from the base module
     * with context and callback
     *
     * @uses xarResponse::Forbidden()
     * @param string $msg the message
     * @param mixed $context
     * @param ?string $template override forbidden template
     * @return string output display string
     */
    public static function forbidden($msg = '', $context = null, $template = null)
    {
        return self::ctl()->forbidden($msg, $template);
    }

    /**
     * Return a 404 Not Found header, and fill in the template message-notfound.xt from the base module
     * with context and callback
     *
     * @uses xarResponse::NotFound()
     * @param string $msg the message
     * @param mixed $context
     * @param ?string $template override notfound template
     * @return string output display string
     */
    public static function notFound($msg = '', $context = null, $template = null)
    {
        return self::ctl()->notFound($msg, $template);
    }

    /**
     * Return a 400 Bad Request header, and fill in the template user-errors.xt from the privileges module
     * with context and callback
     *
     * @param ?string $layout default 'bad_author' layout
     * @param mixed $context
     * @return string output display string
     */
    public static function badRequest($layout = null, $context = null)
    {
        return self::ctl()->badRequest($layout);
    }

    /**
     * Summary of setRouter
     * @param xarRouter $router
     * @return void
     */
    public static function setRouter($router)
    {
        self::ctl()->setRouter($router);
    }

    /**
     * Summary of getRouter
     * @return xarRouter
     */
    public static function getRouter()
    {
        return self::ctl()->getRouter();
    }

    /**
     * Summary of getDispatcher
     * @return xarDispatcher
     */
    public static function getDispatcher()
    {
        return self::ctl()->getDispatcher();
    }

    /**
     * Summary of setCallback
     * @param string $name
     * @param ?callable $callable
     * @return void
     */
    public static function setCallback($name, $callable)
    {
        self::ctl()->setCallback($name, $callable);
    }

    /**
     * Summary of getCallback
     * @param string $name
     * @return callable|null
     */
    public static function getCallback($name)
    {
        return self::ctl()->getCallback($name);
    }

    /**
     * Generates a URL that references a module function.
     *
     * @param ?string $modName registered name of module
     * @param string $modType type of function
     * @param string $funcName module function
     * @param string $fragment document fragment target (e.g. somesite.com/index.php?foo=bar#target)
     * @param array<string, mixed> $args array of arguments to put on the URL
     * @param ?bool $generateXMLURL
     * @param string|array<string, mixed> $entrypoint array of arguments for different entrypoint than index.php
     * @param ?string $route
     * @return string absolute URL for call, or false on failure
     * @todo allow for an alternative entry point (e.g. stream.php) without affecting the other parameters
     */
    public static function URL($modName = null, $modType = 'user', $funcName = 'main', $args = [], $generateXMLURL = null, $fragment = null, $entrypoint = [], $route = null)
    {
        return self::ctl()->URL($modName, $modType, $funcName, $args, $generateXMLURL, $fragment, $entrypoint, $route);
    }

    /**
     * Summary of parseQuery
     * @param string $url
     * @return array<string, mixed>
     * @todo take into account routing
     */
    public static function parseQuery($url = '')
    {
        return self::ctl()->parseQuery($url);
    }
}
