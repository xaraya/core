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
use xarHooks;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi gethooklist function
 * @extends MethodClass<AdminApi>
 */
class GethooklistMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Obtain list of hooks (optionally for a particular module)
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['modName'] optional module we're looking for
     * @return array|void of known hooks
     * @see AdminApi::gethooklist()
     */
    public function __invoke(array $args = [])
    {
        // Security Check
        // @CHECKME: is this info not useful to other modules?
        if (!$this->sec()->checkAccess('ManageModules')) {
            return;
        }

        // Get arguments from argument array
        extract($args);

        // get a list of observer (hook) modules
        $hookmods = xarHooks::getObserverModules();


        // reconstruct hooklist[hookmod][object:action:area][hookedto][itemtype] for anyone still using this
        $hooklist = [];
        foreach ($hookmods as $modname => $info) {
            // pointless sanity check
            if (!isset($hooklist[$modname])) {
                $hooklist[$modname] = [];
            }
            // get list of modules / itemtypes this module is hooked to
            $hookedto = xarHooks::getObserverSubjects($modname);
            if (!empty($info['hooks'])) {

                foreach ($info['hooks'] as $event => $hook) {
                    if (!empty($hook['scope'])) {
                        $object = $hook['scope'];
                    } else {
                        $replace = ['modifyconfig', 'updateconfig', 'create', 'delete', 'modify', 'update', 'remove', 'search', 'display', 'waitingcontent', 'init','activate', 'upgrade', 'view', 'submit'];
                        $object = str_replace($replace, '', strtolower($event));
                    }
                    $action = str_replace(strtolower($object), '', strtolower($event));
                    $area = strtolower($hook['area']);
                    if (!isset($hooklist[$modname]["$object:$action:$area"])) {
                        $hooklist[$modname]["$object:$action:$area"] = [];
                    }
                    if (!empty($hookedto)) {
                        foreach ($hookedto as $subject => $itemtypes) {
                            foreach ($itemtypes as $itemtype => $scopes) {
                                if (!empty($scopes[0]) || !empty($scopes[$scope])) {
                                    $hooklist[$modname]["$object:$action:$area"][$subject][$itemtype] = 1;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $hooklist;
    }
}
