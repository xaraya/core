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

/**
 * themes adminapi themedir2name function
 * @extends MethodClass<AdminApi>
 */
class Themedir2nameMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Convert a theme directory to a theme name.
     * @author Roger Keays <r.keays@ninthave.net>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['directory'] of the theme
     * @return string the theme name in this directory, or false if theme is not
     * found
     * @see AdminApi::themedir2name()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        $allthemes = $adminapi->getfilethemes();
        foreach ($allthemes as $theme) {
            if ($theme['directory'] == $args['directory']) {
                return $theme['name'];
            }
        }
        return false;
    }
}
