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
use ixarTheme;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes adminapi list function
 * @extends MethodClass<AdminApi>
 */
class ListMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Obtain list of themes
     * @author Marty Vance
     * @return array|void the known themes
     * @see AdminApi::list()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security Check
        if (!$this->sec()->checkAccess('AdminThemes')) {
            return;
        }

        // Obtain information
        $themeList = $adminapi->GetThemeList(['filter'     => ['State' => ixarTheme::STATE_ANY]]);
        //throw back
        if (!isset($themeList)) {
            return;
        }

        return $themeList;
    }
}
