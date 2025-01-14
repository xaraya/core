<?php

/**
 * Handle module hook calls - @todo does this make sense?
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
    /**
     * Wrapper for xarModHooks::call() - only for migration
     * @param mixed $scope
     * @param mixed $action
     * @param mixed $itemid
     * @param mixed $extraInfo
     * @return mixed output from hooks, or null if there are no hooks
     */
    public function callHooks($scope, $action, $itemid, $extraInfo): mixed;

    /**
     * Wrapper for xarHooks::notify() - only for migration
     * @param string $event
     * @param mixed $info
     * @return mixed output from hooks, or null if there are no hooks
     */
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
     * @see xarHooks::call()
     * @param mixed $scope
     * @param mixed $action
     * @param mixed $itemid
     * @param mixed $extraInfo
     * @return mixed output from hooks, or null if there are no hooks
     */
    public function callHooks($scope, $action, $itemid, $extraInfo = null): mixed
    {
        //return xarModHooks::call($scope, $action, $itemid, $extraInfo, $this->getModName(), $this->getItemType(), $this->getContext());
        // scope and action are concatenated to form the name of the hook event
        $event = ucfirst($scope) . ucfirst($action);
        $extraInfo ??= [];
        $extraInfo['itemid'] ??= $itemid;
        $extraInfo['module'] ??= $this->getModName();
        $extraInfo['itemtype'] ??= $this->getItemType();
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
     * @see xarHooks::notify()
     * @param string $event
     * @param mixed $info
     * @return mixed output from hooks, or null if there are no hooks
     */
    public function notifyHooks($event, $info = []): mixed
    {
        $info['itemid'] ??= null;
        $info['module'] ??= $this->getModName();
        $info['itemtype'] ??= $this->getItemType();
        return xarHooks::notify($event, $info, $this->getContext());
    }
}
