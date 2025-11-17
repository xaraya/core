<?php

/**
 * Module handler class for routing & dispatching outside Xaraya
 *
 * Experiment using module classes and methods as handler
 */

namespace Xaraya\Routing;

use Xaraya\Context\Context;
use Xaraya\Context\ContextTrait;
use Xaraya\Modules\GuiModuleServicesInterface;
use Xaraya\Modules\ModuleServicesInterface;
use Xaraya\Services\WithServicesClass;
use FunctionNotFoundException;

/**
 * Module handler class for routing & dispatching outside Xaraya
 *
 * Supported URLs :
 *
 * ```
 * $pathPrefix/$moduleName/
 * $pathPrefix/$moduleName/admin/{func}
 * $pathPrefix/$moduleName[/user]/{func} (add /user if moduleName == objectName)
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
    use WithServicesClass;

    protected ModuleServicesInterface $instance;
    protected string $funcName;

    /**
     * Summary of __construct
     * @param ModuleServicesInterface $instance
     * @param ?Context<string, mixed> $context
     */
    public function __construct(ModuleServicesInterface $instance, ?Context $context = null)
    {
        $this->instance = $instance;
        $this->setContext($context);
    }

    /**
     * Call the right handler after matching the route
     * @param array<string, mixed> $vars
     * @see \Xaraya\Bridge\Routing\RoutingBridge::callHandler()
     */
    public function callHandler(mixed $handler, array $vars = []): mixed
    {
        $this->context?->tracePath(__METHOD__ . ': ' . $handler[0] . ' ' . $handler[1], $vars);
        $handler = $this->resolveHandler($handler, $vars);
        if (!$handler[0]->hasMethod($handler[1], 'gui')) {
            throw new FunctionNotFoundException($handler[1]);
        }
        unset($vars['_route']);
        // DefaultHandler has no module class instance
        if (!isset($this->instance)) {
            $xar = $this->getServicesClass();
        } else {
            $xar = $this->instance;
        }
        // @todo set request here for MenuBlock::setRequestInfo() in admin menu!?
        $xar->req()->setRequest(['module' => $this->getModName(), 'type' => $this->getModType(), 'func' => $this->funcName]);
        // Note: $this->instance might not be initialized for DefaultHandler
        $this->context?->tracePath(__METHOD__ . ': resolve', [$handler[0]::class, $this->funcName, $vars]);
        $result = $handler($vars);
        // @todo do not apply template here (yet)?
        if (is_array($result) && is_subclass_of($handler[0], GuiModuleServicesInterface::class)) {
            $this->context?->tracePath(__METHOD__ . ': template', [$handler[0]::class, $this->funcName]);
            $result = $handler[0]->mod()->template($this->funcName, $result);
        }
        return [$result, $this->getContext()];
    }

    /**
     * Summary of resolveHandler
     * @param mixed $handler
     * @param array<string, mixed> $vars
     * @return array{0: ModuleServicesInterface, 1: string}
     * @see \Xaraya\Bridge\Routing\RoutingBridge::resolveHandler()
     */
    public function resolveHandler(mixed $handler, array $vars): mixed
    {
        // @todo allow overriding {module}-main route with $vars['type'] and/or $vars['func'] here?
        if (!empty($vars['_route']) && str_ends_with($vars['_route'], '-main')) {
            if (!empty($vars['type']) && $vars['type'] != 'user') {
                $module = $this->instance->getModule();
                $classType = $module->getClassType($vars['type']);
                if (!empty($classType) && $module->hasComponent($classType)) {
                    $handler[0] = $module->getComponent($classType);
                    $handler[1] = $vars['func'] ?? 'main';
                    $this->instance = $handler[0];
                }
            }
        }
        // assuming $handler[0] is \Xaraya\Modules\...\UserGui class here
        if ($handler[1] == 'admingui') {
            // ... replace usergui instance with admingui instance
            $handler[0] = $this->instance->admingui();
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
        $this->funcName = $handler[1];
        return [$this->instance, $this->funcName];
    }

    /**
     * Summary of getInstance
     */
    public function getInstance(): ModuleServicesInterface
    {
        return $this->instance;
    }

    /**
     * Create output for result - @todo
     * @see \Xaraya\Bridge\Routing\RoutingBridge::output()
     */
    public function output(mixed $result, mixed $transform = null): string
    {
        // @todo apply template here?
        //if (is_array($result) && is_subclass_of($this->instance, GuiModuleServicesInterface::class)) {
        //    $result = $this->instance->mod()->template($this->funcName, $result);
        //}
        if (is_string($result)) {
            return $result;
        }
        return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function getModName(): string
    {
        return $this->instance->getModName();
    }

    public function getModType(): string
    {
        return $this->instance->getModType();
    }

    public function getItemType(): int
    {
        return $this->instance->getItemType();
    }

    public function hasMethod(string $funcName, string $funcType = 'api'): bool
    {
        return $this->instance->hasMethod($funcName, $funcType);
    }
}
