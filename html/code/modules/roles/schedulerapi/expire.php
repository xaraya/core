<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\SchedulerApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\SchedulerApi;
use xarMod;
use xarModVars;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles schedulerapi expire function
 * @extends MethodClass<SchedulerApi>
 */
class ExpireMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * expire non-validated accounts or whatever (executed by the scheduler module)
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @see SchedulerApi::expire()
     */
    public function __invoke(array $args = [])
    {

        // TODO: get some configuration info here if necessary
        // $whatever = xarModVars::get('roles','whatever');
        // ...
        // TODO: we need some API function here (not a GUI function)
        //       It may return true (or some logging text) if it succeeds, and null if it fails
        // return xarMod::apiFunc('roles','admin','...',
        //                      array('whatever' => $whatever));

        return true;
    }
}
