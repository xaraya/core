<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\UserApi;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\UserApi;
use xarSession;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata userapi sessioncontext function (was getcontext)
 * @extends MethodClass<UserApi>
 */
class SessioncontextMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get an array of context data for a module using dynamicdata
     * @author the DynamicData module development team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $module  name of the module dynamicdata is working for
     * @return array of data
     * @see UserApi::sessioncontext()
     */
    public function __invoke($args = ['module' => 'dynamicdata'])
    {
        // @todo use incoming $this->getContext() here too?
        extract($args);
        /** @var ?string $module */
        $module ??= 'dynamicdata';
        $ddcontext = $this->session()->getVar('ddcontext.' . $module);
        $ddcontext['tplmodule'] = $module;
        return $ddcontext;
    }
}
