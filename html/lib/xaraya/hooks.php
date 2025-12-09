<?php

/**
 * (Module) Hooks handling subsystem - moved from modules to hooks for (future) clarity
 * @todo Hooks are currently linked with modules & itemtypes, not objects
 * @checkme Control actions further and e.g. automatically detect & call hook actions in various places ?
 * @checkme Replace 'module' with 'config' scope to indicate that we actually configure hooks for module itemtypes, objects, etc. there ?
 * @todo <chris> review the above todo's and checkme's
 * @package core\hooks
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @author Chris Powis <crisp@xaraya.com>
 */

// use ixarMod;
use Xaraya\Services\HookedService;
use Xaraya\Services\Modules\HooksHelper;
use Xaraya\Services\xar;

/**
 * @see xar::hooked()
 */
class xarHooks extends xarEvents
{
    // unique event system itemtype ids for storage/retrieval/actioning in the event system
    public const HOOK_SUBJECT_TYPE  = 3;
    public const HOOK_OBSERVER_TYPE = 4;

    protected static $hookobservers = [];
    // allow others to define callback functions without registering observers e.g. for event bridge
    protected static $callbackFunctions = [];

    protected static function service($xar = null): HookedService
    {
        if (!isset(static::$service)) {
            $xar ??= xar::getServicesClass();
            static::$service = $xar->hooked();
        }
        return static::$service;
    }

    /**
     * required functions, provide event system with late static bindings for these values
    **/
    public static function getSubjectType()
    {
        return xarHooks::HOOK_SUBJECT_TYPE;
    }
    public static function getObserverType()
    {
        return xarHooks::HOOK_OBSERVER_TYPE;
    }

    /**
     * public event registration functions
     *
    **/
    public static function registerSubject($event, $scope, $module, $classnameOrArea = 'class', $type = 'hooksubjects', $func = 'notify')
    {
        return static::service()->registerSubject($event, $scope, $module, $classnameOrArea, $type, $func);
    }

    public static function registerObserver($event, $module, $classnameOrArea = 'class', $type = 'hookobservers', $func = 'notify')
    {
        return static::service()->registerObserver($event, $module, $classnameOrArea, $type, $func);
    }

    // this function is called when an event is raised (hook called)
    // it returns all modules hooked to the caller module (+ itemtype) that raised the event
    // NOTE: This function is called by the event module, modify with caution
    /**
     * Summary of getObservers
     * @param ixarHookSubject $subject
     * @return array<mixed>|void
     */
    public static function getObservers(ixarEventSubject $subject)
    {
        $xar = $subject->getServicesClass();
        return static::service($xar)->getObservers($subject);
    }

    /**
     * See if a hook module (observer) is attached (hooked) to specific module (subject) (+ itemtype)
     * @return bool true if the observer is attached, false otherwise (for any reason)
    **/
    public static function isAttached($observer, $subject, $itemtype = null, $scope = "0")
    {
        return static::service()->isAttached($observer, $subject, $itemtype, $scope);
    }

    /**
     * Hook system functions
    **/
    /**
     * Attach (hook) a hook module (observer) to a module (subject) (+ itemtype)
    **/
    public static function attach($observer, $subject, $itemtype = null, $scope = "0")
    {
        return static::service()->attach($observer, $subject, $itemtype, $scope);
    }

    /**
     * Detach (unhook) a hook module (observer) from a module (subject) (+ itemtype)
    **/
    public static function detach($observer, $subject, $itemtype = null, $scope = null)
    {
        return static::service()->detach($observer, $subject, $itemtype, $scope);
    }

    /**
     * Get the list of hook modules (observers) and their available subject observers (hooks)
     * @param string $observer, name of module supplying hooks
    **/
    public static function getObserverModules($observer = null, $xar = null)
    {
        return static::service($xar)->getObserverModules($observer);
    }

    /**
     * Get the list of modules (subjects) (+itemtypes) a hook module (observer) is hooked to
    **/
    public static function getObserverSubjects($observer, $subject = null, $scope = null, $xar = null)
    {
        return static::service($xar)->getObserverSubjects($observer, $subject, $scope);
    }

    /**
     * Get a list of hook modules (observers) attached (hooked)
     * to a specific module (subject) (+itemtype) event
    **/
    public static function getSubjectObservers($subject, $event, $itemtype = null, $xar = null)
    {
        return static::service($xar)->getSubjectObservers($subject, $event, $itemtype);
    }

}

/**
 * Hook operations for modules
 *
 * @package core\hooks
 * @deprecated 2.8.5 use xar::hooked() or xar::mod()->*Hooks instead
 */
class xarModHooks extends xarObject
{
    protected static ?HooksHelper $helper = null;

    protected static function helper($xar = null): HooksHelper
    {
        if (!isset(static::$helper)) {
            $xar ??= xar::getServicesClass();
            static::$helper = $xar->service('modules.hooks');
        }
        return static::$helper;
    }

    /**
     * Carry out hook operations for module
     *
     * @access public
     * @param $hookScope string the scope the hook is called for - 'item', 'module', ...
     * @param $hookAction string the action the hook is called for - 'transform', 'display', 'new', 'create', 'delete', ...
     * @param $hookId mixed the id of the object the hook is called for (module-specific)
     * @param $extraInfo mixed extra information for the hook, dependent on hookAction
     * @param $callerModName string for what module are we calling this (default = current main module)
     *        Note : better pass the caller module via $extraInfo['module'] if necessary, so that hook functions receive it too
     * @param $callerItemType string optional item type for the calling module (default = none)
     *        Note : better pass the item type via $extraInfo['itemtype'] if necessary, so that hook functions receive it too
     * @param $context mixed optional context for the hook call (default = none)
     * @return mixed output from hooks, or null if there are no hooks
     * @throws BadParameterException
     */
    public static function call($hookScope, $hookAction, $hookId, $extraInfo = null, $callerModName = null, $callerItemType = '', $context = null)
    {
        return self::helper()->callHooks($hookScope, $hookAction, $hookId, $extraInfo, $callerModName, $callerItemType);
    }

    /**
     * Get list of available hooks for a particular module[, scope] and action
     *
     * @access public
     * @param $callerModName string name of the calling module
     * @param $hookScope string the hook scope
     * @param $hookAction string the hook action
     * @param $callerItemType string optional item type for the calling module (default = none)
     * @return array<mixed> of hook information arrays, or null if database error
     */
    public static function getList($callerModName, $hookScope, $hookAction, $callerItemType = '')
    {
        return self::helper()->getList($callerModName, $hookScope, $hookAction, $callerItemType);
    }

    /**
     * Check if a particular hook module is hooked to the current module (+ itemtype)
     *
     * @access public
     * @param $hookModName string name of the hook module we're looking for
     * @param $callerModName string name of the calling module (default = current)
     * @param $callerItemType string optional item type for the calling module (default = none)
     * @return bool true if the module is hooked, false otherwise (for any reason)
     */
    public static function isHooked($hookModName, $callerModName = null, $callerItemType = '')
    {
        return self::helper()->isHooked($hookModName, $callerModName, $callerItemType);
    }

    /**
     * register a hook function
     *
     * @access public
     * @param $hookScope the hook scope
     * @param $hookAction the hook action
     * @param $hookArea the area of the hook (either 'GUI' or 'API')
     * @param $hookModName name of the hook module
     * @param $hookModType name of the hook type ('user' / 'admin' / ... for regular functions, or 'class' for hook call handlers)
     * @param $hookModFunc name of the hook function or handler class
     * @return array<mixed>|void true on success
     * @throws BadParameterException
     */
    public static function register($hookScope, $hookAction, $hookArea, $hookModName, $hookModType, $hookModFunc)
    {
        return self::helper()->register($hookScope, $hookAction, $hookArea, $hookModName, $hookModType, $hookModFunc);
    }

    /**
     * unregister a hook function (deprecated - use unregisterHookModule or the standard deinstall for modules instead)
     *
     * @access public
     * @param $hookScope the hook scope
     * @param $hookAction the hook action
     * @param $hookArea the area of the hook (either 'GUI' or 'API')
     * @param $hookModName name of the hook module
     * @param $hookModType name of the hook type ('user' / 'admin' / ... for regular functions, or 'class' for hook call handlers)
     * @param $hookModFunc name of the hook function or handler class
     * @return bool true if the unregister call suceeded, false if it failed
     * @throws BadParameterException
     */
    public static function unregister($hookScope, $hookAction, $hookArea, $hookModName, $hookModType, $hookModFunc)
    {
        return self::helper()->unregister($hookScope, $hookAction, $hookArea, $hookModName, $hookModType, $hookModFunc);
    }
}
