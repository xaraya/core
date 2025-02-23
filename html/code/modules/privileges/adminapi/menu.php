<?php

/**
 * @package modules\privileges
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Privileges\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Privileges\AdminApi;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges adminapi menu function
 * @extends MethodClass<AdminApi>
 */
class MenuMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * generate the common admin menu configuration
     * @see AdminApi::menu()
     */
    public function __invoke(array $args = [])
    {
        // Initialise the array that will hold the menu configuration
        $menu = [];

        // Specify the menu title to be used in your blocklayout template
        $menu['menutitle'] = $this->ml('Privileges Administration');

        // Preset some status variable
        $menu['status'] = '';

        // Return the array containing the menu configuration
        return $menu;
    }
}
