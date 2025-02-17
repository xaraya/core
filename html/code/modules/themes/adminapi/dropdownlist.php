<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\AdminApi;
use xarMod;
use xarTheme;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes adminapi dropdownlist function
 * @extends MethodClass<AdminApi>
 */
class DropdownlistMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @see AdminApi::dropdownlist()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        $themelist = $adminapi->getthemelist($args);
        $options = [];
        if (!empty($themelist)) {
            foreach ($themelist as $theme) {
                if (isset($args['Class']) && $theme['class'] != $args['Class']) {
                    continue;
                }
                $options[] = ['id' =>  $theme['name'], 'name' => $theme['displayname']];
            }
        }

        return $options;

    }
}
