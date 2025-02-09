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
 * Note: requests with module = object or prefix = /object are handed off to DataObjectRequest
 */
class ModuleRequest extends BasicBridge implements ModuleBridgeInterface
{
    use ModuleBridgeTrait;

    public function setContext($context)
    {
        $this->mod()->setContext($context);
        parent::setContext($context);
    }
}

class ModuleGuiRequest extends ModuleRequest
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

class ModuleApiRequest extends ModuleRequest
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
