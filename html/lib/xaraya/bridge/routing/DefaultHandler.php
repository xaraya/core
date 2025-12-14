<?php

/**
 * Default handler class for routing & dispatching outside Xaraya
 *
 * Experiment using module classes and methods as handler
 */

namespace Xaraya\Routing;

/**
 * Default handler class for routing & dispatching outside Xaraya
 *
 * Supported URLs :
 *
 * ```
 * /
 * /{module}/ (with trailing / here)
 * /{module}/{func}
 * /{module}/{type}/{func}
 * ```
 * @phpstan-import-type RouteDef from ModuleHandler
 */
class DefaultHandler extends ModuleHandler
{
    // parent for modules service here
    protected string $modName = '';
    protected string $modType = '';
    protected int $itemType = 0;

    public function __construct($xar = null)
    {
        $this->setServicesClass($xar);
        // ... no module class instance here
    }

    /**
     * Summary of resolveHandler
     * @param mixed $handler
     * @param array<string, mixed> $vars
     * @return array{0: HandlerInterface, 1: string}
     */
    public function resolveHandler(mixed $handler, array $vars): mixed
    {
        // handle default routes here
        $this->funcName = $handler[1];
        return [$this, $this->funcName];
    }

    /**
     * Handle default routes for other modules
     * @param array<string, mixed> $args
     * @throws \FunctionNotFoundException
     * @return mixed
     */
    public function handle(array $args = [])
    {
        $modName = $args['module'] ?? 'base';
        $modType = $args['type'] ?? 'user';
        $funcName = $args['func'] ?? 'main';
        unset($args['module']);
        unset($args['type']);
        unset($args['func']);
        // parent for modules service here - @todo not anymore
        $this->modName = $modName;
        $this->modType = $modType;
        $this->funcName = $funcName;
        $xar = $this->getServicesClass();
        $xar->setModName($this->getModName());
        // @todo (re-)set request here for MenuBlock::setRequestInfo() in admin menu!?
        $xar->req()->setRequest(['module' => $this->getModName(), 'type' => $this->getModType(), 'func' => $this->funcName]);
        $result = $xar->mod()->guiMethod($modName, $modType, $funcName, $args);
        // always apply template here
        if (is_array($result)) {
            $result = $xar->mod()->render($funcName, $result);
        }
        return $result;
    }

    public function routes(array $args = [])
    {
        $dispatcher = new Dispatcher();
        $result = "<ol>";
        foreach ($dispatcher->getRoutes() as $name => $route) {
            $result .= "<li>" . $name . ": " . json_encode($route, JSON_UNESCAPED_SLASHES) . "</li>";
        }
        $result .= "</ol>";
        return $result;
    }

    #[\Override]
    public function getModName(): string
    {
        return $this->modName;
    }

    #[\Override]
    public function getModType(): string
    {
        return $this->modType;
    }

    #[\Override]
    public function getItemType(): int
    {
        return $this->itemType;
    }

    #[\Override]
    public function hasMethod(string $funcName, string $funcType = 'api'): bool
    {
        return method_exists($this, $funcName);
    }
}
