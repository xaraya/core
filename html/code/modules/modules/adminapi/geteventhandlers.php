<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminApi;
use PHPException;
use xarConfigVars;
use xarMod;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi geteventhandlers function
 * @extends MethodClass<AdminApi>
 */
class GeteventhandlersMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get the list of active event handlers
     * @author Xaraya Development Team
     * @return bool null on exceptions, true on sucess to update
     * @deprecated 2.4.0 replaced with xarEvent code and event observers
     * @see AdminApi::geteventhandlers()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        static $check = true;  // switch to always true

        //Now with dependency checking, this function may be called multiple times
        //Let's check if it already return ok and stop the processing here
        if ($check) {
            return true;
        }

        $modlist = $adminapi->getlist(['filter' => ['State' => xarMod::STATE_ACTIVE]]);

        $todo = [];
        // @todo: this looks familiar, xarEvt.php has the same?
        foreach ($modlist as $mod) {
            $modName = $mod['name'];
            $modDir = $mod['osdirectory'];
            // use the directory here, not the name
            $xarapifile = "modules.{$modDir}.xareventapi";
            // try to include the event API for this module
            try {
                // @todo does this need to be wrapped for multiple inclusion?
                sys::import($xarapifile);
                $modName = strtolower($modName);
                $todo[$modName] = $modDir;
            } catch (PHPException $e) {
                // No harm done, well
            }
        }

        $handlers = [];
        if (count($todo) > 0) {
            // get the list of all defined functions
            $functions = get_defined_functions();
            // get the list of all relevant modules
            $filter = join('|', array_keys($todo));
            // see if we have some <module>_eventapi_on<eventname> functions
            foreach ($functions['user'] as $userfunc) {
                if (preg_match("/^($filter)_eventapi_on(.+)$/i", $userfunc, $matches)) {
                    $modname = $matches[1];
                    $eventname = $matches[2];
                    if (!empty($todo[$modname])) {
                        if (!isset($handlers[$eventname])) {
                            $handlers[$eventname] = [];
                        }
                        // save the module directory here too
                        $handlers[$eventname][$modname] = $todo[$modname];
                    } else {
                        // ignore event handlers from unknown/inactive modules
                    }
                }
            }
        }
        // this gets serialized internally
        $this->config()->setVar('Site.Evt.Handlers', $handlers);

        $check = true;

        return true;
    }
}
