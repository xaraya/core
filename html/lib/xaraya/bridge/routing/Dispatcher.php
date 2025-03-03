<?php

/**
 * Module dispatcher for routing & dispatching outside Xaraya
 */

namespace Xaraya\Routing;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Xaraya\Context\Context;
use xarClassMap;
use xarController;
use xarServer;
use Exception;
use FunctionNotFoundException;

/**
 * Module dispatcher for routing & dispatching outside Xaraya
 */
class Dispatcher
{
    public string $baseUri = '';
    public ?RouterInterface $router;
    public ?HandlerInterface $handler;

    public function __construct(string $baseUri = 'http://localhost/')
    {
        $this->baseUri = $baseUri;
    }

    public function dispatch(string $path, string $method = 'GET', array $params = [])
    {
        [$handler, $vars] = $this->getRouter()->match($path, $method);
        if (empty($handler)) {
            return [$vars, null];
        }
        $this->prepareController($this->baseUri);
        if (!empty($params)) {
            $vars = array_merge($vars, $params);
        }
        [$result, $context] = $this->callHandler($handler, $vars);
        return [$result, $context];
    }

    /**
     * Create output for result - @todo
     * @see \Xaraya\Bridge\Routing\RoutingBridge::output()
     */
    public function output(mixed $result, mixed $transform = null): string
    {
        if (is_string($result)) {
            return $result;
        }
        return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function getRouter()
    {
        if (!isset($this->router)) {
            $this->router = new Routing($this->getRoutes(...));
        }
        return $this->router;
    }

    public function getRoutes()
    {
        $routes = [];
        $handlers = xarClassMap::getHandlers();
        foreach ($handlers as $className => $filePath) {
            $routes = array_merge($routes, $className::getRoutes());
        }
        $routes = array_merge($routes, DefaultHandler::getRoutes());
        return $routes;
    }

    /**
     * Instantiate and call the handler returned by match() if it has HandlerInterface
     * @param mixed $handler
     * @param array<string, mixed> $vars
     * @param ?Context<string, mixed> $context
     * @throws \Exception
     * @return array<mixed>
     */
    public function callHandler(mixed $handler, array $vars, ?Context $context = null)
    {
        if (empty($handler)) {
            // @todo see status in Routing::match()
            throw new Exception('Invalid handler');
        }
        [$handlerClass, $method] = $handler;
        if (!is_subclass_of($handlerClass, HandlerInterface::class)) {
            throw new Exception('Unknown handler class ' . $handlerClass);
        }
        $this->handler = new $handlerClass();
        $this->handler->setContext($context);
        try {
            [$result, $context] = $this->handler->callHandler($handler, $vars);
        } catch (FunctionNotFoundException $e) {
            $result = $this->notFound($e->getMessage(), $context);
        }
        return [$result, $context];
    }

    /**
     * Summary of getHandler
     * @return HandlerInterface|null
     */
    public function getHandler(): HandlerInterface|null
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
        xarServer::setBaseURL($baseUri);
        xarController::setCallback('buildUri', [$this, 'buildUri']);
        xarController::setCallback('redirectTo', [$this, 'redirect']);
        xarController::setCallback('forbiddenTo', [$this, 'forbidden']);
        xarController::setCallback('notFoundTo', [$this, 'notFound']);
        xarController::setCallback('badRequestTo', [$this, 'badRequest']);
    }

    /**
     * Basic route builder for object/module requests e.g. in response output or templates - using route names here
     * @param array<string, mixed> $extra
     * @see \Xaraya\Bridge\Middleware\DefaultRouter::buildUri()
     */
    public function buildUri(?string $arg1 = null, ?string $arg2 = null, string|int|null $arg3 = null, array $extra = []): string
    {
        echo "/$arg1-$arg2-$arg3/" . rawurldecode(json_encode($extra));
        $route = null;
        if (!empty($extra['_route'])) {
            $route = $extra['_route'];
            unset($extra['_route']);
        } else {
            $handlers = [];
            if (!empty($arg1)) {
                if ($arg1 == 'object') {
                    $arg1 = 'dynamicdata';
                    $extra['entity'] ??= $arg2;
                    $extra['action'] ??= $arg3;
                    // @todo replace [itemid] with 1234567890 for defer* properties
                    if (!empty($extra['itemid']) && $extra['itemid'] == '[itemid]') {
                        $extra['action'] = $extra['itemid'];
                        unset($extra['itemid']);
                    }
                } else {
                    $extra['module'] ??= $arg1;
                    $extra['type'] ??= $arg2;
                    $extra['func'] ??= $arg3;
                }
                $handlers = xarClassMap::getHandlers($arg1);
            }
            if (empty($handlers)) {
                $handlers = xarClassMap::getHandlers();
            }
            foreach ($handlers as $className => $filePath) {
                $route = $className::findRoute($extra);
                if (isset($route)) {
                    break;
                }
            }
        }
        if (!empty($route)) {
            // clean up current module
            if (!empty($arg1) && !empty($extra['module']) && $extra['module'] == $arg1) {
                unset($extra['module']);
            }
            // clean up default action
            if (!empty($extra['entity']) && !empty($extra['action']) && in_array($extra['action'], ['view', 'display'])) {
                unset($extra['action']);
            }
        } else {
            $route = DefaultHandler::findRoute($extra);
            // clean up default type
            if (!empty($extra['type']) && $extra['type'] == 'user') {
                unset($extra['type']);
            }
        }
        try {
            return $this->getRouter()->generate($route, $extra);
            // @todo replace 1234567890 with [itemid] for defer* properties
        } catch (RouteNotFoundException $e) {
            // ...
        }
        // @todo find route based on args
        return "/$arg1-$arg2-$arg3/" . rawurldecode(json_encode($extra));
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
        echo "Redirect: $redirectURL ($httpResponse)";
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
        echo "Not Found: $msg";
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
        echo "Bad Request: $layout";
        return null;
    }
}
