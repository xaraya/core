<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminApi;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminApi;

/**
 * dynamicdata adminapi menu function
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
        $menu['menutitle'] = $this->ml('Dynamic Data Administration');
        // Preset some status variable
        $menu['status'] = '';
        // Return the array containing the menu configuration
        return $menu;
    }
}
