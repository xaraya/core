<?php

/**
 * Modules Service Helper for Module Hooks
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services\Modules;

use Xaraya\Context\Context;
use Xaraya\Services\ServiceClass;

/**
 * Modules Service Helper for Module Hooks
 * @deprecated 2.9.3 use xar::hooked() instead
 */
class HooksHelper extends ServiceClass
{
    public const SLICE = 'modules.hooks';

    /**
     * See if a hook module (observer) is attached (hooked) to specific module (subject) (+ itemtype)
     */
    public function isHooked(string $hookModName, string $callerModName, ?int $callerItemType = null): bool
    {
        $xar = $this->getServicesClass();
        $hookedConfig = $xar->hooked()->getConfigService();
        return $hookedConfig->isAttached($hookModName, $callerModName, $callerItemType);
    }

    /**
     * Wrapper for xarModHooks::call() - only for migration
     * @return mixed output from hooks, or null if there are no hooks
     */
    public function callHooks(string $scope, string $action, mixed $itemid, mixed $extraInfo = null, string $callerModName = '', ?int $callerItemType = null): mixed
    {
        // scope and action are concatenated to form the name of the hook event
        $event = ucfirst($scope) . ucfirst($action);
        $extraInfo ??= [];
        $extraInfo['itemid'] ??= $itemid;
        $extraInfo['module'] ??= $callerModName;
        $extraInfo['itemtype'] ??= $callerItemType;
        $xar = $this->getServicesClass();
        // skip legacy format here - handled by HookSubject if needed
        return $xar->hooked()->notify($event, $extraInfo, $this->getContext());
    }

    /**
     * Wrapper for xarHooks::notify() - only for migration
     * @param array<string, mixed> $info
     * @param ?Context<string, mixed> $context
     * @return mixed output from hooks, or null if there are no hooks
     */
    public function notifyHooks(string $event, array $info = [], ?Context $context = null): mixed
    {
        $xar = $this->getServicesClass();
        $info['itemid'] ??= null;
        // @todo check if we'll have context here
        $context ??= $this->getContext();
        return $xar->hooked()->notify($event, $info, $context);
    }

    public function getList($callerModName, $hookScope, $hookAction, $callerItemType = '')
    {
        $xar = $this->getServicesClass();
        $event = ucfirst($hookScope) . ucfirst($hookAction);
        return $xar->hooked()->getSubjectObservers($callerModName, $event, $callerItemType);
    }

    public function register($hookScope, $hookAction, $hookArea, $hookModName, $hookModType, $hookModFunc)
    {
        $xar = $this->getServicesClass();
        $event = ucfirst($hookScope) . ucfirst($hookAction);
        return $xar->hooked()->registerObserver($event, $hookModName, $hookArea, $hookModType, $hookModFunc);
    }

    public function unregister($hookScope, $hookAction, $hookArea, $hookModName, $hookModType, $hookModFunc)
    {
        $xar = $this->getServicesClass();
        $event = ucfirst($hookScope) . ucfirst($hookAction);
        return $xar->hooked()->unregisterObserver($event, $hookModName);
    }
}
