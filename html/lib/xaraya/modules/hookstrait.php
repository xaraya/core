<?php

/**
 * Handle module hook calls
 *
 * @package core\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.5.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules;

use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;
use xarHooks;

//use xarModHooks;

/**
 * For documentation purposes only - available via HooksTrait
 */
interface HooksInterface extends ContextInterface
{
    public function callHooks($scope, $action, $itemid, $extraInfo): mixed;
    public function notifyHooks($event, $info = []): mixed;
}

/**
 * Trait to handle hook calls
 */
trait HooksTrait
{
    use ContextTrait;

    protected string $moduleName;          // set in constructor by ModuleTrait::createComponent()
    protected int $itemtype = 0;

    /**
     * Wrapper for xarModHooks::call() - only for migration
     * @param mixed $scope
     * @param mixed $action
     * @param mixed $itemid
     * @param mixed $extraInfo
     * @return mixed output from hooks, or null if there are no hooks
     */
    public function callHooks($scope, $action, $itemid, $extraInfo = null): mixed
    {
        //return xarModHooks::call($scope, $action, $itemid, $extraInfo, $this->moduleName, $this->itemtype, $this->getContext());
        // scope and action are concatenated to form the name of the hook event
        $event = ucfirst($scope) . ucfirst($action);
        $extraInfo ??= [];
        $extraInfo['itemid'] ??= $itemid;
        $extraInfo['module'] ??= $this->moduleName;
        $extraInfo['itemtype'] ??= $this->itemtype;
        // skip legacy format here - handled by HookSubject if needed
        //$args = [
        //    'objectid' => $itemid,
        //    'extrainfo' => $extraInfo,
        //];
        //return $this->notifyHooks($event, $args);
        return $this->notifyHooks($event, $extraInfo);
    }

    /**
     * Wrapper for xarHooks::notify() - only for migration
     * @param string $event
     * @param mixed $info
     * @return mixed output from hooks, or null if there are no hooks
     */
    public function notifyHooks($event, $info = []): mixed
    {
        $info['itemid'] ??= null;
        $info['module'] ??= $this->moduleName;
        $info['itemtype'] ??= $this->itemtype;
        return xarHooks::notify($event, $info, $this->getContext());
    }
}
