<?php

/**
 * Controller available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use xarController;
use xarResponse;
use xarRequest;
use xarServer;
use xarSystemVars;
use xarDDObject;
use sys;

sys::import('xaraya.services.servicetrait');

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
    public function init(array $config = [], mixed $context = null): bool;
    /** @return array<string, mixed> */
    public function getConfig(): array;
    public function dispatch(xarRequest $request, mixed $context = null): void;
    public function normalizeRequest(): void;
    public function setCallback(string $name, ?callable $callback): void;
    public function getCallback(string $name): ?callable;
    public function setRequest(?string $url = null): void;
    public function setResponse(?xarResponse $response = null): void;
    public function getResponse(): xarResponse;

    public function setRouter(?\Xaraya\Routing\RouterInterface $router): void;

    public function setBaseURL(?string $baseurl): void;
    public function getPageTime(): float;

    /**
     * Get base url
     */
    public function getBaseURL(): string;

    /**
     * Get current url
     * @param array<string, mixed> $params
     */
    public function getCurrentURL(array $params = [], ?bool $generateXMLURL = null): string;

    /**
     * Get entry point = index.php or custom
     */
    public function getEntryPoint(): string;

    public function getServerVar(string $varName): mixed;

    public function getSystemVar(string $varName): mixed;
    public function getCurrentRequestString(array $params = [], ?bool $generateXMLURL = null, ?string $target = null): string;

    /**
     * Get base uri
     */
    public function getBaseURI(): string;

    /**
     * Get current request
     * @return xarRequest
     */
    public function getRequest(): xarRequest;
    public function getRequestVar(string $varName, ?string $allowOnlyMethod = null): mixed;
    public function getRequestMethod(): string;
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

    protected static ?\Xaraya\Routing\RouterInterface $router = null;

    /**
     * Get url for a module type function
     * @param array<string, mixed> $params
     */
    public function getModuleURL(?string $modName = null, string $modType = 'user', string $funcName = 'main', array $params = [], ?bool $generateXMLURL = null): string
    {
        return xarController::URL($modName, $modType, $funcName, $params, $generateXMLURL);
    }

    /**
     * Get url for an object method
     * @param array<string, mixed> $params
     */
    public function getObjectURL(?string $objectName = null, string $methodName = 'view', array $params = [], ?bool $generateXMLURL = null): string
    {
        return xarServer::getObjectURL($objectName, $methodName, $params, $generateXMLURL);
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
        if (empty(self::$router)) {
            $cacheFile = sys::varpath() . '/cache/core/' . \Xaraya\Routing\Routing::MATCHER_CACHE_FILE;
            self::$router = new \Xaraya\Routing\Routing(\Xaraya\Routing\Dispatcher::getRoutes(...), $cacheFile);
        }
        // @todo handle $baseURL + $entryPoint somehow!?
        // ...
        try {
            $path = self::$router->generate($route, $params);
        } catch (\Exception $e) {
            return null;
        }
        return $this->getBaseURL() . ltrim($this->getEntryPoint() . $path, '/');
    }

    public function setRouter(?\Xaraya\Routing\RouterInterface $router): void
    {
        self::$router = $router;
    }

    /** @param array<string, mixed> $config */
    public function init(array $config = [], mixed $context = null): bool
    {
        // this will be relying on RequestService in the future
        return xarController::init($config, $context);
    }

    /** @return array<string, mixed> */
    public function getConfig(): array
    {
        return xarController::getConfig();
    }

    public function dispatch(xarRequest $request, mixed $context = null): void
    {
        xarController::dispatch($request, $context);
    }

    public function normalizeRequest(): void
    {
        xarController::normalizeRequest();
    }

    public function setCallback(string $name, ?callable $callback): void
    {
        xarController::setCallback($name, $callback);
    }

    public function getCallback(string $name): ?callable
    {
        return xarController::getCallback($name);
    }

    public function setRequest(?string $url = null): void
    {
        // this will be relying on RequestService in the future
        xarController::setRequest($url);
    }

    public function setResponse(?xarResponse $response = null): void
    {
        xarController::setResponse($response);
    }

    public function getResponse(): xarResponse
    {
        return xarController::getResponse();
    }

    public function setBaseURL(?string $baseurl): void
    {
        xarServer::setBaseURL($baseurl);
    }

    public function getPageTime(): float
    {
        // this will be relying on RequestService in the future
        return xarServer::getPageTime();
    }

    /**
     * Get current url
     * @param array<string, mixed> $params
     */
    // this will be relying on RequestService in the future (getCurrentURL)
    public function getCurrentURL(array $params = [], ?bool $generateXMLURL = null): string
    {
        return xarServer::getCurrentURL($params, $generateXMLURL);
    }

    /**
     * Get base url
     */
    public function getBaseURL(): string
    {
        return xarServer::getBaseURL();
    }

    public function getCurrentRequestString(array $params = [], ?bool $generateXMLURL = null, ?string $target = null): string
    {
        return xarServer::getCurrentRequestString($params, $generateXMLURL, $target);
    }

    /**
     * Get base uri
     */
    // this will be relying on RequestService in the future (getBaseURI)
    public function getBaseURI(): string
    {
        return xarServer::getBaseURI();
    }

    /**
     * Get entry point = index.php or custom
     */
    public function getEntryPoint(): string
    {
        return xarController::$entryPoint;
    }

    /**
     * Get a server variable
     * @return mixed
     */
    // this will be relying on RequestService in the future (getServerVar)
    public function getServerVar(string $varName): mixed
    {
        return xarServer::getVar($varName);
    }

    /**
     * Get a system config variable
     * @return mixed
     */
    public function getSystemVar(string $varName): mixed
    {
        return xarSystemVars::get(sys::CONFIG, $varName);
    }

    /**
     * Get current request
     * @return xarRequest
     */
    // this will be relying on RequestService in the future (getRequest)
    public function getRequest(): xarRequest
    {
        return xarController::getRequest();
    }

    /**
     * Get a request variable
     * @return mixed
     */
    // this will be relying on RequestService in the future (getVar)
    public function getRequestVar(string $varName, ?string $allowOnlyMethod = null): mixed // @todo rename to getVar
    {
        return xarController::getVar($varName, $allowOnlyMethod);
    }

    // this will be relying on RequestService in the future (getMethod)
    public function getRequestMethod(): string // @todo rename to getMethod
    {
        return xarServer::getVar('REQUEST_METHOD') ?? 'GET';
    }

    // this will be relying on RequestService in the future (isLocalReferer)
    public function isLocalReferer(): bool
    {
        return xarController::isLocalReferer();
    }

    // this will be relying on RequestService in the future (isSameReferer)
    public function isSameReferer(): bool
    {
        return xarController::isRefererSameModule();
    }

    /**
     * Send redirect to url and exit
     * @uses xarController::redirect()
     * @return bool|never
     */
    public function redirect(string $url, ?int $httpResponse = null)
    {
        return xarController::redirect($url, $httpResponse, $this->getContext());
    }

    /**
     * Return a 403 Forbidden header and fill in the 'message-forbidden.xt' template
     * @return string|null output display string
     */
    public function forbidden(string $msg = '', ?string $template = null): ?string
    {
        return xarController::forbidden($msg, $this->getContext(), $template);
    }

    /**
     * Return a 404 Not Found header and fill in the 'message-notfound.xt' template
     * @return string|null output display string
     */
    public function notFound(string $msg = '', ?string $template = null): ?string
    {
        return xarController::notFound($msg, $this->getContext(), $template);
    }

    /**
     * Return a 400 Bad Request header and fill in the 'user-errors.xt' template with optional layout
     * @return string|null output display string
     */
    public function badRequest(?string $layout = null): ?string
    {
        return xarController::badRequest($layout, $this->getContext());
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
