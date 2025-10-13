<?php

/**
 * Routing based on nikic/fast-route library
 * @see https://github.com/nikic/FastRoute
 */

namespace Xaraya\Routing;

use FastRoute\ConfigureRoutes;
use FastRoute\Dispatcher;
use FastRoute\FastRoute;
use FastRoute\GenerateUri;
use FastRoute\GenerateUri\UriCouldNotBeGenerated;
use Exception;
use Throwable;

/**
 * Routing based on nikic/fast-route library
 * @phpstan-type ExtraParameters array<string, string|int|bool|float>
 */
class FastRouter implements RouterInterface
{
    public const FASTROUTE_CACHE_FILE = 'url_fastroute_cache.php';

    /** @var callable|null */
    protected $callable = null;
    protected string $cacheFile = '';
    /** @var FastRoute|null */
    protected $fastRoute = null;
    /** @var Dispatcher|null */
    protected $dispatcher = null;
    /** @var GenerateUri|null */
    protected $uriGenerator = null;
    /** @var array<mixed> */
    public array $trackRoutes = [];

    /**
     * Summary of __construct
     * @param callable $callable get array of name => [method(s), path, handler, options = []]
     * @param string $cacheFile leave empty to disable router cache
     */
    public function __construct($callable, $cacheFile = '')
    {
        $this->callable = $callable;
        $this->cacheFile = $cacheFile;
        $this->trackRoutes = [];
    }

    /**
     * Summary of getFastRoute
     * @return FastRoute
     */
    public function getFastRoute()
    {
        if (isset($this->fastRoute)) {
            return $this->fastRoute;
        }
        $cacheKey = $this->cacheFile ?: 'fastroute.cache';
        $this->fastRoute = FastRoute::recommendedSettings($this->addRouteCollection(...), $cacheKey);
        if (empty($this->cacheFile)) {
            $this->fastRoute = $this->fastRoute->disableCache();
        }
        return $this->fastRoute;
    }

    /**
     * Summary of getDispatcher
     * @return Dispatcher
     */
    public function getDispatcher()
    {
        $this->dispatcher ??= $this->getFastRoute()->dispatcher();
        return $this->dispatcher;
    }

    /**
     * Summary of getUriGenerator
     * @return GenerateUri
     */
    public function getUriGenerator()
    {
        $this->uriGenerator ??= $this->getFastRoute()->uriGenerator();
        return $this->uriGenerator;
    }

    /**
     * Summary of addRouteCollection
     * @param ConfigureRoutes $r
     * @return void
     */
    public function addRouteCollection(ConfigureRoutes $r)
    {
        // only called when needed
        $routes = ($this->callable)();
        foreach ($routes as $name => $route) {
            // add extra options if needed
            $route[] = [];
            /** @var array<string, string|int|bool|float> $options */
            [$methods, $path, $handler, $options] = $route;
            if (isset($this->trackRoutes[$name])) {
                throw new Exception('Duplicate route name ' . $name . ' for ' . $path);
            }
            // set route name in extra options for uri generator - FastRoute uses _name internally
            $options[ConfigureRoutes::ROUTE_NAME] ??= $name;
            $this->trackRoutes[$name] = [$methods, $path, $handler, $options];
            $r->addRoute($methods, $path, $handler, $options);
        }
    }

    /**
     * Match path with optional method
     * @param string $path
     * @param ?string $method
     * @return array<mixed> array of [handler, path params] or [null, ['status' => 40x]] if not found
     */
    public function match($path, $method = null)
    {
        $params = [];
        $method ??= 'GET';

        $dispatcher = $this->getDispatcher();
        $routeInfo = $dispatcher->dispatch($method, $path);
        $handler = null;
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                /** @var \FastRoute\Dispatcher\Result\NotMatched $routeInfo */
                // ... 404 Not Found
                //http_response_code(404);
                //throw new Exception("Invalid route " . htmlspecialchars($path));
                return [null, ['status' => 404]];
            case Dispatcher::METHOD_NOT_ALLOWED:
                /** @var \FastRoute\Dispatcher\Result\MethodNotAllowed $routeInfo */
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                //header('Allow: ' . implode(', ', $allowedMethods));
                //http_response_code(405);
                //throw new Exception("Invalid method " . htmlspecialchars($method) . " for route " . htmlspecialchars($path));
                return [null, ['status' => 405, 'methods' => $allowedMethods]];
            case Dispatcher::FOUND:
                /** @var \FastRoute\Dispatcher\Result\Matched $routeInfo */
                // handler specified for route
                $handler = $routeInfo[1];
                // path params found by dispatcher
                $params = $routeInfo[2];
                // extra options defined in route (_name) or set by route collector (_route = regex path)
                $extra = $routeInfo->extraParameters;
        }
        // set _route param in request once we find matching route - FastRoute uses _name internally
        if (isset($extra[ConfigureRoutes::ROUTE_NAME]) && !isset($params[self::ROUTE_PARAM])) {
            $params[self::ROUTE_PARAM] = $extra[ConfigureRoutes::ROUTE_NAME];
        }
        return [$handler, $params];
    }

    /**
     * Generate URL path for route name and params
     * @param string $name
     * @param array<string, mixed> $params
     * @return string|null
     */
    public function generate($name, $params)
    {
        $generator = $this->getUriGenerator();
        /** @var array<non-empty-string, non-empty-string> $params */
        $params = array_map("strval", $params);
        // @todo slugify & rawurlencode title & author
        // @todo add fixed params!?
        // @todo add remaining params in query string
        try {
            return $generator->forRoute($name, $params);
        } catch (UriCouldNotBeGenerated $e) {
            error_log($e);
            echo $e;
            return null;
        } catch (Throwable $e) {
            // preg_match() issue like TypeError if param wasn't a string
            error_log($e);
            echo $e;
            return null;
        }
    }

    /**
     * Make URI for route name and params or return null
     * @param array<string, mixed> $params
     */
    public function makeUri(?string $route, array $params = []): ?string
    {
        // we have a route in params
        if (!empty($params[self::ROUTE_PARAM])) {
            if (empty($route)) {
                $route = $params[self::ROUTE_PARAM];
            }
            // clean up current route
            unset($params[self::ROUTE_PARAM]);
        }
        if (empty($route)) {
            return null;
        }
        return $this->generate($route, $params);
    }

    /**
     * Check last modified cache file against reference file
     * @param string $filePath
     * @return void
     */
    public function checkCache($filePath)
    {
        if (empty($filePath) || !file_exists($filePath)) {
            return;
        }
        if (empty($this->cacheFile) || !file_exists($this->cacheFile)) {
            return;
        }
        if (filemtime($this->cacheFile) < filemtime($filePath)) {
            unlink($this->cacheFile);
        }
    }

    /**
     * Get list of loaded routes
     * @return array<mixed>
     */
    public function getRoutes(): array
    {
        // @todo handle cached routes
        if (!empty($this->cacheFile) && file_exists($this->cacheFile)) {
            $routes = include $this->cacheFile;
            return $routes[2] ?? [];
        }
        return $this->trackRoutes;
    }
}
