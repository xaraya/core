<?php

/**
 * HTTP Protocol URL/Server utilities
 *
 * @package core
 * @subpackage server
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marco Canini <marco@xaraya.com>
 * @author Michel Dalle <mikespub@xaraya.com>
**/

use Xaraya\Requests\RequestInterface as RequestFacade;
use Xaraya\Services\ControllerService;
use Xaraya\Services\RequestService;
use Xaraya\Services\xar;

/**
 * @deprecated 2.8.4 use xar::req() or xar::ctl() instead
 */
class xarServer extends xarObject
{
    public const PROTOCOL_HTTP  = 'http';
    public const PROTOCOL_HTTPS = 'https';

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
     * Initialise the Server Support (required)
     * @param array<string, mixed> $args
     * @param mixed $context
     * @return bool true
     */
    public static function init(array $args = [], $context = null)
    {
        // static cache for migration
        self::$ctlService = null;
        self::$reqService = null;
        return self::req()->init($args);
    }

    /**
     * Get server configuration
     * @return array<string, mixed>
     */
    public static function getConfig()
    {
        return self::req()->getConfig();
    }

    /**
     * Set the request class to use (instead of RequestHandler)
     * @param class-string $className
     * @return void
     */
    public static function setRequestClass($className)
    {
        self::req()->setRequestClass($className);
    }

    /**
     * Get the request class instance (on demand)
     * @return RequestFacade
     */
    public static function getInstance()
    {
        return self::req()->getInstance();
    }

    /**
     * Set the request class instance
     * @param RequestFacade $instance
     * @return void
     */
    public static function setInstance($instance)
    {
        self::req()->setInstance($instance);
    }

    /**
     * Summary of newInstance
     * @param mixed $context
     * @return RequestFacade
     */
    public static function newInstance($context = null)
    {
        return self::req()->newInstance();
    }

    /**
     * Gets a server variable
     *
     * Returns the value of $name server variable.
     * Accepted values for $name are exactly the ones described by the
     * {@link http://www.php.net/manual/en/reserved.variables.server.php PHP manual}.
     * If the server variable doesn't exist null is returned.
     *
     * @param string $name the name of the variable
     * @return mixed value of the variable
     */
    public static function getVar($name)
    {
        return self::req()->getServerVar($name);
    }

    /**
     * Allow setting server variable if needed
     * @param string $name the name of the variable
     * @param mixed $value value of the variable
     * @return void
     */
    public static function setVar($name, $value)
    {
        self::req()->setServerVar($name, $value);
    }

    /**
     * Get base URI for Xaraya
     *
     * @return string base URI for Xaraya
     * @todo remove whatever may come after the PHP script - TO BE CHECKED !
     * @todo See code comments.
     */
    public static function getBaseURI()
    {
        return self::req()->getBaseURI();
    }

    /**
     * Gets the host name
     *
     * Returns the server host name fetched from HTTP headers when possible.
     * The host name is in the canonical form (host + : + port) when the port is different than 80.
     *
     * @return string HTTP host name
     */
    public static function getHost()
    {
        return self::req()->getHost();
    }

    /**
     * Gets the current protocol
     *
     * Returns the HTTP protocol used by current connection, it could be 'http' or 'https'.
     *
     * @return string current HTTP protocol
     */
    public static function getProtocol()
    {
        return self::req()->getProtocol();
    }

    /**
     * get base URL for Xaraya
     *
     * @return string base URL for Xaraya
     */
    public static function getBaseURL()
    {
        return self::ctl()->getBaseURL();
    }

    /**
     * Allow setting baseurl if needed
     * @param ?string $baseurl
     * @return void
     */
    public static function setBaseURL($baseurl)
    {
        self::ctl()->setBaseURL($baseurl);
    }

    /**
     * get the elapsed time since this page started
     *
     * @return float seconds and microseconds elapsed since the page started
     */
    public static function getPageTime()
    {
        return self::ctl()->getPageTime();
    }

    /**
     * Get current URL (and optionally add/replace some parameters)
     *
     * @param array<string, mixed> $args additional parameters to be added to/replaced in the URL (e.g. theme, ...)
     * @param ?bool $generateXMLURL over-ride Server default setting for generating XML URLs (true/false/NULL)
     * @param ?string $target add a 'target' component to the URL
     * @return string current URL
     * @todo cfr. BaseURI() for other possible ways, or try PHP_SELF
     */
    public static function getCurrentURL($args = [], $generateXMLURL = null, $target = null)
    {
        return self::ctl()->getCurrentURL($args, $generateXMLURL, $target);
    }

    /**
     * Get current query string (and optionally add/replace some parameters)
     *
     * @param array<string, mixed> $args additional parameters to be added to/replaced in the URL (e.g. theme, ...)
     * @param ?bool $generateXMLURL over-ride Server default setting for generating XML URLs (true/false/NULL)
     * @param ?string $target add a 'target' component to the URL
     * @return string current query string
     */
    public static function getCurrentRequestString($args = [], $generateXMLURL = null, $target = null)
    {
        // does not support $generateXMLURL or $target here - see self::ctl()->getCurrentURL()
        return self::req()->getRequestString($args);
    }

    /**
     * Generates an URL that reference to a module function.
     *
     * Cfr. xarMod URL() in modules
     * @param ?string $modName registered name of module
     * @param string $modType type of function
     * @param string $funcName module function
     * @param array<string, mixed> $args additional parameters to be added to/replaced in the URL (e.g. theme, ...)
     * @param ?bool $generateXMLURL over-ride Server default setting for generating XML URLs (true/false/NULL)
     * @param ?string $fragment document fragment target (e.g. somesite.com/index.php?foo=bar#target)
     * @param string|array<string, mixed> $entrypoint array of arguments for different entrypoint than index.php
     * @return mixed absolute URL for call, or false on failure
     */
    public static function getModuleURL($modName = null, $modType = 'user', $funcName = 'main', $args = [], $generateXMLURL = null, $fragment = null, $entrypoint = [])
    {
        // Note: fragment and endpoint are not supported here
        $url = self::ctl()->getModuleURL($modName, $modType, $funcName, $args, $generateXMLURL);

        // Add the fragment if required.
        if (isset($fragment)) {
            $url .= '#' . urlencode($fragment);
        }

        // Return the URL.
        return $url;
    }

    /**
     * Generates a URL that reference to an object user interface method.
     * @param ?string $objectName
     * @param string $methodName
     * @param array<string, mixed> $args additional parameters to be added to/replaced in the URL (e.g. theme, ...)
     * @param ?bool $generateXMLURL over-ride Server default setting for generating XML URLs (true/false/NULL)
     * @param ?string $fragment document fragment target (e.g. somesite.com/index.php?foo=bar#target)
     * @param string|array<string, mixed> $entrypoint array of arguments for different entrypoint than index.php
     * @return string absolute URL for call, or false on failure
     */
    public static function getObjectURL($objectName = null, $methodName = 'view', $args = [], $generateXMLURL = null, $fragment = null, $entrypoint = [])
    {
        // Note: fragment and endpoint are not supported here
        $url = self::ctl()->getObjectURL($objectName, $methodName, $args, $generateXMLURL);

        // Add the fragment if required.
        if (isset($fragment)) {
            $url .= '#' . urlencode($fragment);
        }

        // Return the URL.
        return $url;
    }
}
