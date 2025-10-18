<?php

/**
 * @package core\bridge
 * @subpackage requests
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Requests;

/**
 * Handle Module requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 *
 * Note: requests with module = object or prefix = /object are handed off to DataObjectRequestHandler
 * @phpstan-import-type RouteDef from BasicBridge
 */
class ModuleRequestHandler extends BasicBridge implements ModuleBridgeInterface
{
    use ModuleBridgeTrait;

    /**
     * Get Module handler routes (in generic format)
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param ?string $handler
     * @param array<mixed> $extra
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = '', ?string $handler = null, array $extra = []): array
    {
        return static::getModuleRoutes($pathPrefix, $namePrefix, $handler, $extra);
    }

    public function setContext($context)
    {
        parent::setContext($context);
        $this->mod()->setContext($context);
    }
}

class ModuleGuiHandler extends ModuleRequestHandler
{
    /**
     * Summary of runModuleRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @return string|null
     */
    public function runModuleRequest($vars, $query): ?string
    {
        return $this->runModuleGuiRequest($vars, $query);
    }
}

class ModuleApiHandler extends ModuleRequestHandler
{
    /**
     * Summary of runModuleRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @return mixed
     */
    public function runModuleRequest($vars, $query): mixed
    {
        return $this->runModuleApiRequest($vars, $query);
    }
}
