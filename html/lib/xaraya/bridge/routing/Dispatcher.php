<?php

/**
 * Module dispatcher for routing & dispatching outside Xaraya
 */

namespace Xaraya\Routing;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Xaraya\Bridge\GraphQL\GraphQLRoutes;
use Xaraya\Bridge\GraphQL\GraphQLHandler;
use Xaraya\Bridge\RestAPI\RestAPIRoutes;
use Xaraya\Bridge\RestAPI\RestAPIHandler;
use Xaraya\Context\Context;
use Xaraya\Context\WithContextInterface;
use Xaraya\Context\WithContextTrait;
use Xaraya\Services\WithServicesTrait;
use xarClassMap;
use sys;
use Exception;
use FunctionNotFoundException;

/**
 * Module dispatcher for routing & dispatching outside Xaraya
 */
class Dispatcher implements WithContextInterface
{
    use WithContextTrait;
    use WithServicesTrait;

    public string $baseUri = '';
    public string $basePath = '';
    public ?RouterInterface $router;
    public HandlerInterface|RestAPIHandler|GraphQLHandler|null $handler;
    /** @var ?Context<string, mixed> */
    protected $context = null;

    public function __construct(string $baseUri = 'http://localhost/', ?RouterInterface $router = null, $xar = null)
    {
        $this->baseUri = $baseUri;
        $this->router = $router;
        if (!empty($this->baseUri)) {
            $basePath = parse_url($this->baseUri, PHP_URL_PATH);
            // for http://localhost/xaraya/dispatch.php this becomes /xaraya/dispatch.php
            $this->basePath = $basePath ? rtrim($basePath, '/') : '';
        }
        $this->setServicesClass($xar);
    }

    /**
     * Summary of dispatch
     * @param string $path
     * @param array<string, mixed> $params
     * @param string $method
     * @return array<mixed>
     */
    public function dispatch(string $path, array $params = [], string $method = 'GET')
    {
        // remove basePath to get PATH_INFO if needed
        if (!empty($this->basePath) && str_starts_with($path, $this->basePath . '/')) {
            $path = substr($path, strlen($this->basePath));
        }
        [$handler, $vars] = $this->getRouter()->match($path, $method);
        if (empty($handler)) {
            return [$vars, null];
        }
        $this->prepareController($this->baseUri);
        if (!empty($params)) {
            $vars = array_merge($vars, $params);
        }
        // @todo create context from globals in calling script if needed
        if (empty($this->context)) {
            $this->context = new Context(['source' => __METHOD__]);
            // Set context for core services here first!?
            // xar::setServicesContext($this->context);
        }
        // $this->context->enableTrace(true);
        [$result, $context] = $this->callHandler($handler, $vars);
        return [$result, $context];
    }

    /**
     * Set headers for result - optional if caller wants this
     * @see \Xaraya\Bridge\Routing\RoutingBridge::output()
     */
    public function setHeaders(mixed $result): void
    {
        $context = $this->getContext();
        if (empty($context)) {
            return;
        }
        if (!empty($context['redirectURL'])) {
            header('Location: ' . $context['redirectURL']);
            \xarCore::exit();
            return;
        }
        if (!empty($context['mediatype'])) {
            $mediaType = $context['mediatype'];
            if (!str_contains($mediaType, '; charset=')) {
                $mediaType .= '; charset=utf-8';
            }
            header('Content-Type: ' . $mediaType);
        } elseif (!is_string($result)) {
            $mediaType = 'application/json; charset=utf-8';
            header('Content-Type: ' . $mediaType);
        }
    }

    /**
     * Create output for result - @todo
     * @see \Xaraya\Bridge\Routing\RoutingBridge::output()
     */
    public function output(mixed $result, mixed $transform = null): string
    {
        if (!empty($this->context['redirectURL'])) {
            // let caller deal with setting header("Location: ...") or equivalent
            return '';
        }
        if (is_null($result)) {
            $result = $this->context?->getArrayCopy();
            //return '';
        }
        if (is_string($result)) {
            // @todo transform by using wrapOutputInPage() here
            if (!empty($transform) && empty($this->context['mediatype'])) {
                return $this->wrapOutputInPage($result);
            }
            return $result;
        }
        return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Summary of wrapOutputInPage
     * @param string $body
     * @param mixed $context
     * @return string
     */
    public function wrapOutputInPage(string $body): string
    {
        $this->context?->tracePath(__METHOD__);
        $xar = $this->getServicesClass();
        $tpl = $xar->tpl();
        // Set page template based on modType if logged in - see index.php
        if (is_a($this->handler, ModuleHandler::class)) {
            $modType = $this->handler->getModType();
            // we need $context['cookie'] and/or $context['server'] for this - see ContextFactory::fromGlobals()
            if (!empty($this->context?->getUserId($xar))) {
                $tpl->setPageTemplateName($modType);
            }
        }
        // Render page with the output - see index.php
        return $tpl->renderPage($body, null, $this->context);
    }

    /**
     * Summary of getRouter
     * @return RouterInterface
     */
    public function getRouter()
    {
        if (!isset($this->router)) {
            $cacheFile = sys::varpath() . '/cache/core/' . Routing::MATCHER_CACHE_FILE;
            $this->router = new Routing($this->getRoutes(...), $cacheFile);
            // parse classmap if necessary
            $handlers = xarClassMap::getRoutes();
            $classMapFile = sys::varpath() . '/cache/' . xarClassMap::PARSED_CACHE_FILE;
            $this->router->checkCache($classMapFile);
        }
        return $this->router;
    }

    /**
     * Get routes from all module handlers + default handler
     * @return array<string, array<mixed>>
     */
    public static function getRoutes()
    {
        $routes = [];
        $handlers = xarClassMap::getRoutes();
        /** @var class-string<RoutesInterface> $className */
        foreach ($handlers as $className => $filePath) {
            $routes = array_merge($routes, $className::getRoutes());
        }
        $routes = array_merge($routes, RestAPIRoutes::getRoutes('/restapi'));
        $routes = array_merge($routes, GraphQLRoutes::getRoutes());
        $routes = array_merge($routes, DefaultRoutes::getRoutes());
        return $routes;
    }

    /**
     * Instantiate and call the handler returned by match() if it has RoutesInterface
     * @param mixed $handler
     * @param array<string, mixed> $vars
     * @throws \Exception
     * @return array<mixed>
     */
    public function callHandler(mixed $handler, array $vars)
    {
        if (empty($handler)) {
            // @todo see status in Routing::match()
            throw new Exception('Invalid handler');
        }
        $this->context?->tracePath(__METHOD__, $handler);
        [$routesClass, $method] = $handler;
        if (is_subclass_of($routesClass, RoutesInterface::class)) {
            /** @var class-string<RoutesInterface> $routesClass */
            $route = $vars[RouterInterface::ROUTE_PARAM] ?? '';
            $xar = $this->getServicesClass();
            $this->handler = $routesClass::getHandler($route, $this->context, $xar);
        } else {
            $this->handler = is_object($routesClass) ? $routesClass : new $routesClass();
            $this->handler->setContext($this->context);
        }
        if (method_exists($this->handler, 'setServicesClass')) {
            $xar = $this->getServicesClass();
            $this->handler->setServicesClass($xar);
        }
        try {
            [$result, $context] = $this->handler->callHandler($handler, $vars);
        } catch (FunctionNotFoundException $e) {
            $result = $this->notFound($e->getMessage(), $this->context);
        }
        return [$result, $context];
    }

    /**
     * Summary of getHandler
     * @return HandlerInterface|RestAPIHandler|GraphQLHandler|null
     */
    public function getHandler(): HandlerInterface|RestAPIHandler|GraphQLHandler|null
    {
        return $this->handler;
    }

    /**
     * Summary of prepareController
     * @return void
     * @see \Xaraya\Bridge\Requests\BasicBridgeTrait::prepareController()
     */
    public function prepareController(string $baseUri)
    {
        $ctl = $this->getServicesClass()->ctl();
        $ctl->setBaseURL($baseUri);
        $ctl->setCallback('buildUri', [$this, 'buildUri']);
        $ctl->setCallback('redirectTo', [$this, 'redirect']);
        $ctl->setCallback('forbiddenTo', [$this, 'forbidden']);
        $ctl->setCallback('notFoundTo', [$this, 'notFound']);
        $ctl->setCallback('badRequestTo', [$this, 'badRequest']);
    }

    /**
     * Summary of resetController
     * @return void
     */
    public function resetController()
    {
        $ctl = $this->getServicesClass()->ctl();
        $ctl->setBaseURL(null);
        $ctl->setCallback('buildUri', null);
        $ctl->setCallback('redirectTo', null);
        $ctl->setCallback('forbiddenTo', null);
        $ctl->setCallback('notFoundTo', null);
        $ctl->setCallback('badRequestTo', null);
    }

    /**
     * Basic route builder for object/module requests e.g. in response output or templates - using route names here
     * @param array<string, mixed> $extra
     * @see \Xaraya\Bridge\Middleware\DefaultRouter::buildUri()
     */
    public function buildUri(?string $arg1 = null, ?string $arg2 = null, string|int|null $arg3 = null, array $extra = []): string
    {
        $router = $this->getRouter();
        if (!empty($extra['_route'])) {
            $route = $extra['_route'];
            unset($extra['_route']);
            try {
                return $this->basePath . $router->generate($route, $extra);
                // @todo replace 1234567890 with [itemid] for defer* properties
            } catch (RouteNotFoundException $e) {
                // ...
            }
        }
        $modName = null;
        if (!empty($arg1)) {
            if ($arg1 == 'object') {
                $modName = 'dynamicdata';
                $extra['entity'] ??= $arg2;
                $extra['action'] ??= $arg3;
                // @todo replace [itemid] with 1234567890 for defer* properties
                if (!empty($extra['itemid']) && $extra['itemid'] == '[itemid]') {
                    $extra['action'] = $extra['itemid'];
                    unset($extra['itemid']);
                }
            } else {
                $modName = $arg1;
                $extra['module'] ??= $arg1;
                $extra['type'] ??= $arg2;
                $extra['func'] ??= $arg3;
            }
        }
        // find route uri based on params
        return $this->basePath . $router->findRoute($modName, $extra);
    }

    /**
     * Summary of redirect
     * @param mixed $redirectURL
     * @param mixed $httpResponse
     * @param mixed $context
     * @return null
     */
    public function redirect($redirectURL, $httpResponse, $context)
    {
        // echo "Redirect: $redirectURL ($httpResponse)";
        return null;
    }

    /**
     * Summary of forbidden
     * @param mixed $msg
     * @param mixed $context
     * @return null
     */
    public function forbidden($msg, $context)
    {
        echo "Forbidden: $msg";
        return null;
    }

    /**
     * Summary of notFound
     * @param mixed $msg
     * @param mixed $context
     * @return null
     */
    public function notFound($msg, $context)
    {
        echo "Not Found: $msg (404)";
        return null;
    }

    /**
     * Summary of badRequest
     * @param mixed $layout
     * @param mixed $context
     * @return null
     */
    public function badRequest($layout, $context)
    {
        echo "Bad Request: $layout (400)";
        return null;
    }
}
