<?php

/**
 * Module handler class for routing & dispatching outside Xaraya
 *
 * Experiment using module classes and methods as handler
 */

namespace Xaraya\Routing;

use Xaraya\Context\ContextTrait;
use Xaraya\Modules\ModuleServicesInterface;
use FunctionNotFoundException;

/**
 * Module handler class for routing & dispatching outside Xaraya
 *
 * Supported URLs :
 *
 * ```
 * $pathPrefix/$moduleName/
 * $pathPrefix/$moduleName/admin/{func} (not used here)
 * $pathPrefix/$moduleName[/user]/{func} (not used here)
 * $pathPrefix/$objectName/{entity}
 * $pathPrefix/$objectName/{entity}/{itemid} (numeric)
 * $pathPrefix/$objectName/{entity}/{itemid}/{title}
 * $pathPrefix/$objectName/{entity}/{action} (non-numeric)
 * $pathPrefix/$objectName/{entity}/{action}/{itemid}
 * ```
 * @phpstan-type RouteDef array{0: string|array<string>, 1: string, 2: mixed, 3: array<string, mixed>}
 */
class ModuleHandler implements HandlerInterface
{
    use ContextTrait;

    protected ModuleServicesInterface $instance;
    protected string $funcName;

    /**
     * Summary of __construct
     * @param ModuleServicesInterface $instance
     */
    public function __construct(ModuleServicesInterface $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Call the right handler after matching the route
     * @param array<string, mixed> $vars
     * @see \Xaraya\Bridge\Routing\RoutingBridge::callHandler()
     */
    public function callHandler(mixed $handler, array $vars = []): mixed
    {
        $this->getContext()?->tracePath(__METHOD__, $handler);
        $handler = $this->getHandler($handler);
        // @todo allow overriding {module}-main route with $vars['type'] and/or $vars['func'] here?
        if (!empty($vars['_route']) && str_ends_with($vars['_route'], '-main')) {
            if (!empty($vars['type']) && $vars['type'] != 'user') {
                $module = $handler[0]->getModule();
                $classType = $module->getClassType($vars['type']);
                if (!empty($classType) && $module->hasComponent($classType)) {
                    $handler[0] = $module->getComponent($classType);
                    $handler[1] = $vars['func'] ?? 'main';
                }
            }
        }
        unset($vars['_route']);
        // assuming $handler[0] is \Xaraya\Modules\...\UserGui class here
        if ($handler[1] == 'admingui') {
            // ... replace usergui instance with admingui instance
            $handler[0] = $handler[0]->admingui();
            if (empty($handler[0])) {
                throw new FunctionNotFoundException('AdminGui');
            }
            $this->instance = $handler[0];
            // ... replace method with $vars['func']
            $handler[1] = $vars['func'] ?? 'main';
        }
        if ($handler[1] == 'usergui') {
            // ... replace method with $vars['func']
            $handler[1] = $vars['func'] ?? 'main';
        }
        if (!$handler[0]->hasMethod($handler[1], 'gui')) {
            throw new FunctionNotFoundException($handler[1]);
        }
        $this->funcName = $handler[1];
        $result = $handler($vars);
        // @todo do not apply template here (yet)?
        if (is_array($result)) {
            $result = $handler[0]->mod()->template($handler[1], $result);
        }
        return [$result, $this->getContext()];
    }

    /**
     * Summary of getHandler
     * @param mixed $handler
     * @return array{0: ModuleServicesInterface, 1: string}
     * @see \Xaraya\Bridge\Routing\RoutingBridge::getHandler()
     */
    public function getHandler(mixed $handler): mixed
    {
        $this->funcName = $handler[1];
        return [$this->instance, $this->funcName];
    }

    /**
     * Create output for result - @todo
     * @see \Xaraya\Bridge\Routing\RoutingBridge::output()
     */
    public function output(mixed $result, mixed $transform = null): string
    {
        // @todo apply template here?
        //if (is_array($result)) {
        //    $result = $this->instance->mod()->template($this->funcName, $result);
        //}
        if (is_string($result)) {
            return $result;
        }
        return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
