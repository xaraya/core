<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Base\AdminApi;
use sys;

sys::import('xaraya.modules.method');

/**
 * base adminapi menuarray function
 * @extends MethodClass<AdminApi>
 */
class MenuarrayMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Utility function to create an array for a getmenulinks function
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters
     * @return string[] Menulinks for the module
     * @see AdminApi::menuarray()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        /**
         * Pending
         * @TODO: remove this once all modules are calling loadmenuarray
         */
        // Handle calls from admin getmenulinks functions which haven't yet been updated to use loadmenuarray()
        if (!isset($args['modname']) && isset($args['module'])) {
            // They all use module instead of modname, and are always called by admin type getmenulinks functions
            $args['modname'] = $args['module'];
            $args['modtype'] = 'admin';
            // since loadmenuarray can itself call the getmenulinks function,
            // so that we don't end up in a loop, we request only links in xml files
            $args['nolinks'] = 1;
        }
        // let loadmenuarray do the work
        return $adminapi->loadmenuarray($args);
    }
}
