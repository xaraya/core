<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Categories\AdminApi;

/**
 * categories adminapi getmenulinks function
 * @extends MethodClass<AdminApi>
 */
class GetmenulinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Utility function pass individual menu items to the main menu
     * @author the Example module development team
     * @return array Array containing menulinks for the main menu items.
     * @see AdminApi::getmenulinks()
     */
    public function __invoke(array $args = [])
    {
        return $this->mod()->apiFunc('base', 'admin', 'menuarray', ['module' => 'categories']);

    }
}
