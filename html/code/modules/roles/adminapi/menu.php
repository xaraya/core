<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\AdminApi;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles adminapi menu function
 * @extends MethodClass<AdminApi>
 */
class MenuMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * generate the common admin menu configuration
     * @see AdminApi::menu()
     */
    public function __invoke(array $args = [])
    {
        // Initialise the array that will hold the menu configuration
        $menu = [];

        // Specify the menu title to be used in your blocklayout template
        $menu['menutitle'] = $this->ml('Roles Administration');

        // Preset some status variable
        $menu['status'] = '';

        // Return the array containing the menu configuration
        return $menu;
    }
}
