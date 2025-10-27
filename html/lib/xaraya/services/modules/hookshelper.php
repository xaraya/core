<?php

/**
 * Modules Service Helper for Module Hooks
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services\Modules;

use Xaraya\Context\Context;
use Xaraya\Services\ServiceClass;
use xarHooks;

/**
 * Modules Service Helper for Module Hooks
 */
class HooksHelper extends ServiceClass
{
    public const SLICE = 'modules.hooks';

    /**
     * See if a hook module (observer) is attached (hooked) to specific module (subject) (+ itemtype)
     */
    public function isHooked(string $hookModName, string $callerModName, ?int $callerItemType = null): bool
    {
        return xarHooks::isAttached($hookModName, $callerModName, $callerItemType);
    }

    /**
     * Wrapper for xarModHooks::call() - only for migration
     * @see \xarModHooks::call()
     * @return mixed output from hooks, or null if there are no hooks
     */
    public function callHooks(string $scope, string $action, mixed $itemid, mixed $extraInfo = null, string $callerModName = '', ?int $callerItemType = null): mixed
    {
        //return xarModHooks::call($scope, $action, $itemid, $extraInfo, $this->getModName(), $this->getItemType(), $this->getContext());
        // scope and action are concatenated to form the name of the hook event
        $event = ucfirst($scope) . ucfirst($action);
        $extraInfo ??= [];
        $extraInfo['itemid'] ??= $itemid;
        $extraInfo['module'] ??= $callerModName;
        $extraInfo['itemtype'] ??= $callerItemType;
        // skip legacy format here - handled by HookSubject if needed
        return $this->notifyHooks($event, $extraInfo);
    }

    /**
     * Wrapper for xarHooks::notify() - only for migration
     * @see \xarHooks::notify()
     * @param array<string, mixed> $info
     * @param ?Context<string, mixed> $context
     * @return mixed output from hooks, or null if there are no hooks
     */
    public function notifyHooks(string $event, array $info = [], ?Context $context = null): mixed
    {
        $info['itemid'] ??= null;
        // @todo check if we'll have context here
        $context ??= $this->getContext();
        return xarHooks::notify($event, $info, $context);
    }
}
