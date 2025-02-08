<?php
/**
 * Routing based on Symfony Routing component (test)
 */

namespace Xaraya\Routing;

use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;

/**
 * Routing based on Symfony Routing component (test)
 *
 * Matching URLs is similar to nikic/fast-route, but generating URLs requires known route name
 * @see https://github.com/symfony/symfony/blob/7.1/src/Symfony/Component/Routing/Router.php
 */
class Routing implements RouterInterface
{
    public const HANDLER_PARAM = '_handler';
    public const MATCHER_CACHE_FILE = 'url_matching_routes.php';
    public const GENERATOR_CACHE_FILE = 'url_generating_routes.php';

    /** @var callable|null */
    protected $callable = null;
    public ?string $cacheDir;
    public ?RequestContext $context;
    public ?Router $router;

    /**
     * Summary of __construct
     * @param callable $callable get array of name => [method(s), path, handler, options = []]
     * @param string $cacheFile leave empty to disable router cache
     * @param ?RequestContext $context
     */
    public function __construct($callable, $cacheFile = '', $context = null)
    {
        $this->callable = $callable;
        // force cache generation
        if (!empty($cacheFile)) {
            $this->cacheDir = dirname($cacheFile);
        } else {
            $this->cacheDir = null;
        }
        $this->context = $context;
    }

    /**
     * Get Symfony router for handler routes (cached)
     * @param ?RequestContext $context
     * @param bool $refresh
     * @return Router
     */
    public function getRouter($context = null, $refresh = false)
    {
        if ($refresh) {
            $this->resetCache();
        }
        if (isset($this->router)) {
            if (isset($context)) {
                $this->router->setContext($context);
            }
            return $this->router;
        }
        $loader = new RouteLoader();
        $resource = $this->callable;
        $options = ['cache_dir' => $this->cacheDir];
        $context ??= $this->context;

        $this->router = new Router($loader, $resource, $options, $context);
        return $this->router;
    }

    /**
     * Set router context or reset it
     * @param ?RequestContext $context
     * @return void
     */
    public function setContext($context = null)
    {
        $context ??= new RequestContext();
        $this->getRouter()->setContext($context);
    }

    /**
     * Reset cache files used by UrlMatcher and UrlGenerator
     * @return void
     */
    public function resetCache()
    {
        $cacheFile = $this->cacheDir . '/' . self::MATCHER_CACHE_FILE;
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        $cacheFile = $this->cacheDir . '/' . self::GENERATOR_CACHE_FILE;
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    /*
     * Summary of context - @todo
     * @param mixed $request
     * @return RequestContext
    public function context($request)
    {
        $handler = $request->getHandler();
        $endpoint = Route::endpoint($handler);
        $baseUrl = Route::base() . $endpoint;
        // @todo get scheme and host - see Symfony\Request::getSchemeAndHttpHost()
        //$context = new RequestContext('/index.php', 'GET', 'localhost', 'http', 80, 443, '/', '');
        $context = new RequestContext($baseUrl, $request->method(), 'localhost', 'http', 80, 443, $request->path, $request->query());
        //$context->fromRequest($request);
        return $context;
    }
     */

    /**
     * Match path with optional method
     * @param string $path
     * @param ?string $method
     * @return array<mixed> array of [handler, path params] or [null, ['status' => 40x]] if not found
     */
    public function match($path, $method = null)
    {
        // reset router context to start fresh
        $this->setContext();
        if (!empty($method) && $method != 'GET') {
            // set router context with method
            $this->getRouter()->getContext()->setMethod($method);
        }
        /** @var UrlMatcherInterface $matcher */
        $matcher = $this->getRouter()->getMatcher();
        try {
            $attributes = $matcher->match($path);
        } catch (ResourceNotFoundException $e) {
            // ...
            return [null, ['status' => 404]];
        } catch (MethodNotAllowedException $e) {
            // ...
            return [null, ['status' => 405]];
        }
        $handler = $attributes[self::HANDLER_PARAM] ?? '';
        unset($attributes[self::HANDLER_PARAM]);
        return [$handler, $attributes];
    }

    /**
     * Generate URL path for route name and params
     * @param string $name
     * @param array<string, mixed> $params
     * @return string|null
     */
    public function generate($name, $params)
    {
        $generator = $this->getRouter()->getGenerator();
        try {
            $url = $generator->generate($name, $params, UrlGeneratorInterface::ABSOLUTE_PATH);
        } catch (RouteNotFoundException $e) {
            // ...
            throw $e;
        } catch (InvalidParameterException $e) {
            // ...
            throw $e;
        } catch (MissingMandatoryParametersException $e) {
            // ...
            throw $e;
        }
        return $url;
    }

    /**
     * Get list of loaded routes
     * @return array<mixed>
     */
    public function getRoutes(): array
    {
        if (empty($this->cacheDir)) {
            return [];
        }
        $cacheFile = $this->cacheDir . '/' . self::GENERATOR_CACHE_FILE;
        // @todo generate cache file if needed
        if (!file_exists($cacheFile)) {
            $generator = $this->getRouter()->getGenerator();
        }
        if (file_exists($cacheFile)) {
            $routes = include $cacheFile;
            return $routes ?? [];
        }
        return [];
    }
}
