<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Base\AdminGui;
use xarConfigVars;
use xarCore;
use xarSecurity;
use xarVersion;
use sys;

sys::import('xaraya.modules.method');

/**
 * base admin upgrade function
 * @extends MethodClass<AdminGui>
 */
class UpgradeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Function to upgrade module
     * @author John Cox
     * @return array|void Data for the template display
     * @see AdminGui::upgrade()
     */
    public function __invoke(array $args = [])
    {
        /**
         * Pending
         * @todo change feed url once release module is moved
         */
        // Security
        if (!xarSecurity::check('AdminBase')) {
            return;
        }

        $fileversion = xarCore::VERSION_NUM;
        $dbversion = xarConfigVars::get(null, 'System.Core.VersionNum');
        sys::import('xaraya.version');
        $data['versioncompare'] = xarVersion::compare($fileversion, $dbversion);
        return $data;
    }
}
