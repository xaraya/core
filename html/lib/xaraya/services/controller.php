<?php

/**
 * Controller available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use Xaraya\Routing\RouterInterface;
use xarDispatcher;
use xarRequest;
use xarResponse;
use xarRouter;
use xarDDObject;
use sys;
use Exception;

/**
 * For documentation purposes only - available via ControllerTrait
 */
interface ControllerInterface extends ServiceInterface
{
    public const SLICE = 'controller';

    /**
     * Get url for some module type function
     * @param array<string, mixed> $params
     */
    public function getModuleURL(?string $modName = null, string $modType = 'user', string $funcName = 'main', array $params = [], ?bool $generateXMLURL = null): string;

    /**
     * Get url for some object method
     * @param array<string, mixed> $params
     */
    public function getObjectURL(?string $objectName = null, string $methodName = 'view', array $params = [], ?bool $generateXMLURL = null): string;

    /**
     * Generate URL for a specific action on an object - the format will depend on the linktype
     * @param array<string, mixed> $extra extra arguments to pass to the URL
     */
    public function getActionURL(object $object, string $action = '', mixed $itemid = null, array $extra = []): string;

    /**
     * Get URL for a specific route by name - @todo
     * @param array<string, mixed> $params
     * @see \Xaraya\Routing\Dispatcher::buildUri()
     */
    public function getRouteURL(string $route, array $params = []): ?string;

    /** @param array<string, mixed> $config */
    public function init(array $config = []): bool;
    /** @return array<string, mixed> */
    public function getConfig(): array;
    /** @param array<string, mixed> $config */
    public function setConfig(array $config = []): void;
    public function withXMLURLs(?bool $setXMLURLs = null): bool;
    public function dispatch(xarRequest $request, mixed $context = null): xarResponse;
    public function getCallback(string $name): ?callable;
    public function setCallback(string $name, ?callable $callback): void;
    public function getRequest(mixed $url = null): xarRequest;
    public function normalizeRequest(?xarRequest $request = null): xarRequest;
    public function setRequest(mixed $url = null): void;
    public function getResponse(): xarResponse;
    public function setResponse(?xarResponse $response = null): void;
    public function getRouter(): xarRouter;
    public function setRouter(?xarRouter $router): void;
    public function getDispatcher(): xarDispatcher;
    public function getBaseURL(): string;
    public function setBaseURL(?string $baseurl): void;
    public function getPageTime(): float;

    /**
     * Get current url
     * @param array<string, mixed> $params
     */
    public function getCurrentURL(array $params = [], ?bool $generateXMLURL = null, ?string $target = null): string;

    /**
     * Get entry point = index.php or custom
     */
    public function getEntryPoint(): string;
    public function setEntryPoint(string $entryPoint): void;

    public function getServerVar(string $varName): mixed;

    public function getSystemVar(string $varName): mixed;

    /**
     * Get base uri
     */
    public function getBaseURI(): string;

    public function getRequestVar(string $varName, ?string $allowOnlyMethod = null): mixed;
    public function getRequestMethod(): string;
    /**
     * @return array<string, mixed>
     */
    public function parseQuery(?string $url = null): array;
    public function isLocalReferer(): bool;
    public function isSameReferer(): bool;

    /**
     * Send redirect to url and exit
     * @return bool|never
     */
    public function redirect(string $url, ?int $httpResponse = null);

    /**
     * Return a 403 Forbidden header and fill in the 'message-forbidden.xt' template
     * @return string|null output display string
     */
    public function forbidden(string $msg = '', ?string $template = null): ?string;

    /**
     * Return a 404 Not Found header and fill in the 'message-notfound.xt' template
     * @return string|null output display string
     */
    public function notFound(string $msg = '', ?string $template = null): ?string;

    /**
     * Return a 400 Bad Request header and fill in the 'user-errors.xt' template with optional layout
     * @return string|null output display string
     */
    public function badRequest(?string $layout = null): ?string;
}

/**
 * Controller available via methods
 */
trait ControllerTrait
{
    use ServiceTrait;

    public bool $allowShortURLs = false;
    /** @var array<string, mixed> */
    public $shortURLVariables = [];
    public bool $generateXMLURLs = true;
    public string $delimiter = '?';    // This character divides the URL into action part and parameters
    public string $separator = '&';    // This is the default separator between URL parameters in the default Xaraya route
    /** @var xarDispatcher */
    public $dispatcher;
    /** @var xarRequest */
    public $request;
    /** @var xarResponse */
    public $response;
    /** @var xarRouter */
    public $router;
    public ?string $baseurl = null;
    public string $entryPoint = 'index.php';
    /** @var array<string, ?callable> */
    protected array $callback = [
        'buildUri' => null,      // callable for building URIs when using non-standard entrypoints
        'redirectTo' => null,    // callable for redirecting to when using non-standard entrypoints
        'forbiddenTo' => null,   // callable for forbidden when using non-standard entrypoints
        'notFoundTo' => null,    // callable for not found when using non-standard entrypoints
        'badRequestTo' => null,  // callable for bad request when using non-standard entrypoints
    ];
    protected ?RouterInterface $routing = null;

    /**
     * Get url for a module type function
     * @param array<string, mixed> $params
     */
    public function getModuleURL(?string $modName = null, string $modType = 'user', string $funcName = 'main', array $params = [], ?bool $generateXMLURL = null): string
    {
        return $this->URL($modName, $modType, $funcName, $params, $generateXMLURL);
    }

    /**
     * Get url for an object method
     * @param array<string, mixed> $params
     */
    public function getObjectURL(?string $objectName = null, string $methodName = 'view', array $params = [], ?bool $generateXMLURL = null): string
    {
        // Allow overriding building URL if needed
        $callback = $this->getCallback('buildUri');
        if (!empty($callback) && is_callable($callback)) {
            return call_user_func($callback, 'object', $objectName, $methodName, $params);
        }
        // 1. override any existing 'method' in args, and place before the rest
        if (!empty($methodName)) {
            $params = ['method' => $methodName] + $params;
        }
        // 2. override any existing 'object' or 'name' in args, and place before the rest
        if (!empty($objectName)) {
            unset($params['name']);
            // use 'object' here to distinguish from module URLs
            $params = ['object' => $objectName] + $params;
        }
        // 3. remove default method 'view' from URLs
        if ($params['method'] == 'view') {
            unset($params['method']);
            // and remove default method 'display' from URLs with an itemid
        } elseif (!empty($params['itemid']) && $params['method'] == 'display') {
            unset($params['method']);
        }

        // TODO: some common code for getCurrentURL, getModuleURL and getObjectURL ?

        // Create a new request and make its route the current route
        $params['module'] = 'object';
        $params['type'] = $objectName;
        $params['func'] = $methodName;
        $request = new xarRequest($params, $this->getParent());
        $router = $this->getRouter();
        $request->setRoute($router->getRoute());

        // Get the appropriate action controller for this request
        $dispatcher = $this->getDispatcher();
        $controller = $dispatcher->findController($request);
        $path = $controller->encode($request);

        // Use Xaraya default (index.php) or BaseModURL if provided in config.system.php
        $path = $this->entryPoint . $path;

        // Remove the leading / from the path (if any).
        $path = preg_replace('/^\//', '', $path);

        // Add the fragment if required.
        if (isset($fragment)) {
            $path .= '#' . urlencode($fragment);
        }

        // Encode the URL if an XML-compatible format is required.
        if (!isset($generateXMLURL)) {
            $generateXMLURL = $this->generateXMLURLs;
        }
        if ($generateXMLURL) {
            $path = htmlspecialchars($path);
        }

        // Return the URL.
        return $this->getBaseURL() . $path;
    }

    /**
     * Generate URL for a specific action on an object - the format will depend on the linktype
     *
     * @param object $object the object or object list we want to create an URL for
     * @param string $action the action we want to take on this object (= method or func)
     * @param mixed $itemid the specific item id or null
     * @param array<string, mixed> $extra extra arguments to pass to the URL - CHECKME: we should only need itemid here !?
     * @return string the generated URL
     */
    public function getActionURL(object $object, string $action = '', mixed $itemid = null, array $extra = []): string
    {
        return xarDDObject::getActionURL($object, $action, $itemid, $extra);
    }

    /**
     * Get URL for a specific route by name - @todo
     * @param array<string, mixed> $params
     */
    public function getRouteURL(string $route, array $params = []): ?string
    {
        if (empty($this->routing)) {
            $cacheFile = sys::varpath() . '/cache/core/' . \Xaraya\Routing\Routing::MATCHER_CACHE_FILE;
            $this->routing = new \Xaraya\Routing\Routing(\Xaraya\Routing\Dispatcher::getRoutes(...), $cacheFile);
        }
        // @todo handle $baseURL + $entryPoint somehow!?
        // ...
        try {
            $path = $this->routing->generate($route, $params);
        } catch (Exception $e) {
            return null;
        }
        return $this->getBaseURL() . ltrim($this->getEntryPoint() . $path, '/');
    }

    public function getRouter(): xarRouter
    {
        if (null == $this->router) {
            $this->setRouter(new xarRouter());
            $this->router->addDefaultRoutes($this->getDispatcher());
        }
        return $this->router;
    }

    public function setRouter(?xarRouter $router): void
    {
        $this->router = $router;
    }

    public function getDispatcher(): xarDispatcher
    {
        if (!$this->dispatcher instanceof xarDispatcher) {
            $this->dispatcher = new xarDispatcher();
        }
        return $this->dispatcher;
    }

    /** @param array<string, mixed> $config */
    public function init(array $config = []): bool
    {
        if (empty($config)) {
            $config = $this->getConfig();
        }
        $this->setConfig($config);

        // @todo update $this->endpoint based on actual SCRIPT_NAME?
        // The following allows you to modify the BaseModURL from the config file
        // it can be used to configure Xaraya for mod_rewrite by
        // setting BaseModURL = '' in config.system.php
        try {
            $sysLayout = $this->getParent()->sysConfig(sys::LAYOUT);
            $this->entryPoint = $sysLayout->getVar('BaseModURL');
        } catch (Exception $e) {
            $this->entryPoint = 'index.php';
        }
        return true;
    }

    /** @return array<string, mixed> */
    public function getConfig(): array
    {
        $xar = $this->getParent();
        $systemArgs = [
            'enableShortURLsSupport' => $xar->config()->getVar('Site.Core.EnableShortURLsSupport'),
            // @todo re-evaluate this default
            'generateXMLURLs'        => true,
        ];
        return $systemArgs;
    }

    /** @param array<string, mixed> $config */
    public function setConfig(array $config = []): void
    {
        if (isset($config['enableShortURLsSupport'])) {
            $this->allowShortURLs = $config['enableShortURLsSupport'];
        }
        if (isset($config['generateXMLURLs'])) {
            $this->generateXMLURLs = $config['generateXMLURLs'];
        }
    }

    public function withXMLURLs(?bool $setXMLURLs = null): bool
    {
        if (isset($setXMLURLs)) {
            $this->generateXMLURLs = $setXMLURLs;
        }
        return $this->generateXMLURLs;
    }

    public function dispatch(xarRequest $request, mixed $context = null): xarResponse
    {
        if (!empty($context)) {
            $this->setContext($context);
        }
        try {
            $response = $this->getResponse();
            do {
                $request->setDispatched(true);
                $this->getDispatcher()->dispatch($request, $response);
            } while (!$request->isDispatched());
        } catch (Exception $e) {
            throw $e;
        }
        // @todo return $response here!?
        return $response;
    }

    public function normalizeRequest(?xarRequest $request = null): xarRequest
    {
        $request ??= $this->getRequest();
        $router = $this->getRouter();
        try {
            $router->route($request);
        } catch (Exception $e) {
            throw $e;
        }
        // @todo return $request here!?
        return $request;
    }

    public function setCallback(string $name, ?callable $callback): void
    {
        if (!in_array($name, ['buildUri', 'redirectTo', 'forbiddenTo', 'notFoundTo', 'badRequestTo'])) {
            return;
        }
        $this->callback[$name] = $callback;
    }

    public function getCallback(string $name): ?callable
    {
        if (!in_array($name, ['buildUri', 'redirectTo', 'forbiddenTo', 'notFoundTo', 'badRequestTo'])) {
            return null;
        }
        return $this->callback[$name] ?? null;
    }

    public function setRequest(mixed $url = null): void
    {
        $this->getParent()->req()->setRequest($url);
    }

    public function setResponse(?xarResponse $response = null): void
    {
        $this->response = $response ?? new xarResponse();
    }

    public function getResponse(): xarResponse
    {
        if (empty($this->response)) {
            $this->setResponse();
        }
        return $this->response;
    }

    public function setBaseURL(?string $baseurl): void
    {
        $sysLayout = $this->getParent()->sysConfig(sys::LAYOUT);
        // if entry point is specified in baseurl, e.g. http://localhost/xaraya/dispatch.php
        if (!empty($baseurl) && !str_ends_with($baseurl, '/')) {
            $parts = explode('/', $baseurl);
            $entryPoint = array_pop($parts);
            $this->entryPoint = $entryPoint;
            // @checkme override system config here, since xarController does re-init() for each URL() for some reason...
            $sysLayout->setVar('BaseModURL', $entryPoint);
            $baseurl = substr($baseurl, 0, -strlen($entryPoint));
        }
        $this->baseurl = $baseurl;
        if (empty($baseurl)) {
            $sysLayout->setVar('BaseURI', null);
            // reset entry point to default here
            $this->entryPoint = 'index.php';
            $sysLayout->setVar('BaseModURL', null);
            return;
        }

        $info = parse_url($baseurl);
        // update server vars in request here!
        $req = $this->getParent()->req();
        $req->setServerVar('SERVER_NAME', $info['host']);
        if ($info['scheme'] === 'https') {
            $req->setServerVar('SERVER_PORT', $info['port'] ?? 443);
        } else {
            $req->setServerVar('SERVER_PORT', $info['port'] ?? 80);
        }
        // strip trailing slash for BaseURI here - added again in getBaseURL()
        $sysLayout->setVar('BaseURI', rtrim($info['path'], '/'));
    }

    public function getPageTime(): float
    {
        return microtime(true) - $GLOBALS["Xaraya_PageTime"];
    }

    /**
     * Get current URL (and optionally add/replace some parameters)
     *
     * @param array<string, mixed> $params additional parameters to be added to/replaced in the URL (e.g. theme, ...)
     * @param ?bool $generateXMLURL over-ride Controller default setting for generating XML URLs (true/false/NULL)
     * @param ?string $target add a 'target' component to the URL
     * @return string current URL
     */
    public function getCurrentURL(array $params = [], ?bool $generateXMLURL = null, ?string $target = null): string
    {
        // @checkme use default for controller here
        if (!isset($generateXMLURL)) {
            $generateXMLURL = $this->generateXMLURLs;
        }

        $url = $this->getParent()->req()->getURL($params);

        // Finish up
        if (isset($target)) {
            $url .= '#' . urlencode($target);
        }
        if ($generateXMLURL) {
            $url = htmlspecialchars($url);
        }
        return $url;
    }

    /**
     * Get base url
     */
    public function getBaseURL(): string
    {
        if ($this->baseurl != null) {
            return $this->baseurl;
        }

        $req = $this->getParent()->req();
        $server   = $req->getHost();
        $protocol = $req->getProtocol();
        $path     = $req->getBaseURI();

        $this->baseurl = "$protocol://$server$path/";
        return $this->baseurl;
    }

    /**
     * Get base uri
     */
    public function getBaseURI(): string
    {
        return $this->getParent()->req()->getBaseURI();
    }

    /**
     * Get entry point = index.php or custom
     */
    public function getEntryPoint(): string
    {
        return $this->entryPoint;
    }

    public function setEntryPoint(string $entryPoint): void
    {
        $this->entryPoint = $entryPoint;
    }

    /**
     * Get a server variable
     * @return mixed
     */
    public function getServerVar(string $varName): mixed
    {
        return $this->getParent()->req()->getServerVar($varName);
    }

    /**
     * Get a system config variable
     * @return mixed
     */
    public function getSystemVar(string $varName): mixed
    {
        $sysConfig = $this->getParent()->sysConfig();
        return $sysConfig->getVar($varName);
    }

    /**
     * Get current request
     * @return xarRequest
     */
    public function getRequest(mixed $url = null): xarRequest
    {
        return $this->getParent()->req()->getRequest($url);
    }

    /**
     * Get a request variable
     * @return mixed
     */
    public function getRequestVar(string $varName, ?string $allowOnlyMethod = null): mixed
    {
        return $this->getParent()->req()->getVar($varName, $allowOnlyMethod);
    }

    public function getRequestMethod(): string
    {
        return $this->getParent()->req()->getMethod();
    }

    /**
     * @return array<string, mixed>
     */
    public function parseQuery(?string $url = null): array
    {
        $params = [];
        if (empty($url)) {
            return $params;
        }
        $decomposed = parse_url($url);
        if (isset($decomposed['query'])) {
            $pairs = explode('&', $decomposed['query']);
            try {
                foreach ($pairs as $pair) {
                    if (trim($pair) == '') {
                        continue;
                    }
                    [$key, $value] = explode('=', $pair);
                    $params[$key] = urldecode($value);
                }
            } catch (Exception $e) {
                // ignore
            }
        }
        return $params;
    }

    public function isLocalReferer(): bool
    {
        return $this->getParent()->req()->isLocalReferer();
    }

    public function isSameReferer(): bool
    {
        return $this->getParent()->req()->isSameReferer();
    }

    /**
     * Send redirect to url and exit
     * @return bool|never
     */
    public function redirect(string $url, ?int $httpResponse = null)
    {
        $this->getParent()->cache()->noCache();
        $redirectURL = urldecode($url); // this is safe if called multiple times.

        // Remove &amp; entities to prevent redirect breakage
        $redirectURL = str_replace('&amp;', '&', $redirectURL);

        // default response is temp redirect
        if (!preg_match('/^301|302|303|307/', $httpResponse ?? '')) {
            $httpResponse = 302;
        }

        // Pass along redirectURL and bail out if we have a callback
        $callback = $this->getCallback('redirectTo');
        if (!empty($callback) && is_callable($callback)) {
            if (!empty($this->getContext())) {
                $this->getContext()['redirectURL'] = $redirectURL;
                $this->getContext()->setResponse(null, $httpResponse);
            }
            // Note: let whoever set 'redirectTo' deal with 'buildUri' results (e.g. without protocol://server)
            call_user_func($callback, $redirectURL, $httpResponse, $this->getContext());
            return false;
        }

        // Bail out if we already sent headers
        if (headers_sent()) {
            return false;
        }

        // Note: this doesn't *quite* match the logic in $this->URL() - cfr. entryPoint
        if (substr($redirectURL, 0, 4) != 'http') {
            // Removing leading slashes from redirect url
            $redirectURL = preg_replace('!^\/*!', '', $redirectURL);

            // Get base URL
            $baseurl = $this->getBaseURL();

            $redirectURL = $baseurl . $redirectURL;
        }

        $req = $this->getParent()->req();
        if (preg_match('/IIS/', $req->getServerVar('SERVER_SOFTWARE') ?? '') && preg_match('/CGI/', $req->getServerVar('GATEWAY_INTERFACE') ?? '')) {
            $header = "Refresh: 0; URL=$redirectURL";
        } else {
            $header = "Location: $redirectURL";
        }// if

        // Start all over again
        header($header, true, $httpResponse);

        // NOTE: we *could* return for pure '1 exit point' but then we'd have to keep track of more,
        // so for now, we exit here explicitly. Besides the end of index.php this should be the only
        // exit point.
        \xarCore::exit();
        return false;
    }

    /**
     * Return a 403 Forbidden header and fill in the 'message-forbidden.xt' template
     * @return string|null output display string
     */
    public function forbidden(string $msg = '', ?string $template = null): ?string
    {
        $this->getContext()?->setResponse($msg, 403);
        $callback = $this->getCallback('forbiddenTo');
        if (!empty($callback) && is_callable($callback)) {
            return call_user_func($callback, $msg, $this->getContext());
        }
        return xarResponse::Forbidden($msg, 'base', 'message', 'forbidden', $template, $this->getContext());
    }

    /**
     * Return a 404 Not Found header and fill in the 'message-notfound.xt' template
     * @return string|null output display string
     */
    public function notFound(string $msg = '', ?string $template = null): ?string
    {
        $this->getContext()?->setResponse($msg, 404);
        $callback = $this->getCallback('notFoundTo');
        if (!empty($callback) && is_callable($callback)) {
            return call_user_func($callback, $msg, $this->getContext());
        }
        return xarResponse::NotFound($msg, 'base', 'message', 'notfound', $template, $this->getContext());
    }

    /**
     * Return a 400 Bad Request header and fill in the 'user-errors.xt' template with optional layout
     * @return string|null output display string
     */
    public function badRequest(?string $layout = null): ?string
    {
        $layout ??= 'bad_author';
        $this->getContext()?->setResponse($layout, 400);
        $callback = $this->getCallback('badRequestTo');
        if (!empty($callback) && is_callable($callback)) {
            return call_user_func($callback, $layout, $this->getContext());
        }
        $this->getParent()->cache()->noCache();
        if (!headers_sent()) {
            header('HTTP/1.0 400 Bad Request');
        }
        $tplData = [
            'layout' => $layout,
            'context' => $this->getContext(),
        ];
        return $this->getParent()->tpl()->module('privileges', 'user', 'errors', $tplData);
    }

    /**
     * Get url for a module type function
     * @param array<string, mixed> $params
     */
    public function URL(?string $modName = null, string $modType = 'user', string $funcName = 'main', array $params = [], ?bool $generateXMLURL = null, ?string $fragment = null, mixed $entrypoint = [], ?string $route = null)
    {
        // Allow overriding building URL if needed
        $callback = $this->getCallback('buildUri');
        if (!empty($callback) && is_callable(value: $callback)) {
            // @todo do we need to add baseUri as prefix here?
            return call_user_func($callback, $modName, $modType, $funcName, $params);
        }
        // (Re)initialize the controller
        //self::init();

        // No module specified - just jump to the home page.
        if (empty($modName)) {
            return $this->getBaseURL() . $this->entryPoint;
        }

        // If an entry point has been set, then modify the URL entry point and modType.
        if (!empty($entrypoint)) {
            if (is_array($entrypoint)) {
                $modType = $entrypoint['action'];
                $entrypoint = $entrypoint['entry'];
            }
            $this->entryPoint = $entrypoint;
        }

        // Create a new request and make its route the current route
        $params['module'] = $modName;
        $params['type'] = $modType;
        $params['func'] = $funcName;
        $request = new xarRequest($params, $this->getParent());
        // <chris/> wrt to the problem of xaraya not obeying a particular route
        // when the main entry point, sans params, is accessed...
        // Here's an example using the shorturls setting in base module
        // It's hardly a leap to imagine storing the name of the route to use in a
        // similar config var and being able to set that in base module instead (IMO)
        // assuming multiple routes aren't in use, of course, although we could perhaps
        // deprecate the per module shorturl setting in favour of a dropdown of routes too :-?

        // If we are passed a route, then use it
        if (empty($route)) {
            // No route passed: use the default
            if ($this->allowShortURLs) {
                $route = 'short';
            }
        }
        // Define the route
        if (!empty($route)) {
            $request->setRoute($route);
        } else {
            $router = $this->getRouter();
            $request->setRoute($router->getRoute());
        }

        // Get the appropriate action controller for this request
        $dispatcher = $this->getDispatcher();
        $controller = $dispatcher->findController($request);
        $path = $controller->encode($request);

        // Use Xaraya default (index.php) or BaseModURL if provided in config.system.php
        $path = $this->entryPoint . $path;

        // Remove the leading / from the path (if any).
        $path = preg_replace('/^\//', '', $path);

        // Add the fragment if required.
        if (isset($fragment)) {
            $path .= '#' . urlencode($fragment);
        }

        // @checkme use default for controller here
        // Encode the URL if an XML-compatible format is required.
        // Take the global setting for XML format generation, if not specified.
        if (!isset($generateXMLURL)) {
            $generateXMLURL = $this->generateXMLURLs;
        }
        if ($generateXMLURL) {
            $path = htmlspecialchars($path);
        }

        // Return the URL.
        return $this->getBaseURL() . $path;
    }
}

/**
 * Access xarController::* Main Controller methods (URL, redirect, ...)
 *
 * Available methods:
 * - getModuleURL() - or use mod()->getURL() for current module
 * - getObjectURL() - or use data()->getURL() for current object
 * - getActionURL() - or use $object->getActionURL() with actual object
 * - getRouteURL() - @todo
 * - getCurrentURL()
 * - getBaseURL()
 * - getBaseURI()
 * - getServerVar()
 * - getRequest()
 * - getRequestVar()
 * - getRequestMethod()
 * - isSameReferer()
 * - redirect()
 * - forbidden()
 * - notFound()
 * - badRequest()
 * - ...
 *
 */
class ControllerService implements ControllerInterface
{
    use ControllerTrait;
}
