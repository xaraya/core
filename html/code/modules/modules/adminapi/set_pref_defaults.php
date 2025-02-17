<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminApi;
use xarModUserVars;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi set_pref_defaults function
 * @extends MethodClass<AdminApi>
 */
class SetPrefDefaultsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * reset admin preferences to default module preferences
     * @author Xaraya Development Team
     * @access public
     * @return bool|void true on success, false on failure
     * @see AdminApi::setPrefDefaults()
     */
    public function __invoke(array $args = [])
    {
        // no beating around the bush here
        if (xarModUserVars::get('modules', 'hidecore')) {
            xarModUserVars::delete('modules', 'hidecore');
        }
        if (xarModUserVars::get('modules', 'regen')) {
            xarModUserVars::delete('modules', 'regen');
        }
        if (xarModUserVars::get('modules', 'selstyle')) {
            xarModUserVars::delete('modules', 'selstyle');
        }
        if (xarModUserVars::get('modules', 'selfilter')) {
            xarModUserVars::delete('modules', 'selfilter');
        }
        if (xarModUserVars::get('modules', 'selsort')) {
            xarModUserVars::delete('modules', 'selsort');
        }
        if (xarModUserVars::get('modules', 'hidestats')) {
            xarModUserVars::delete('modules', 'hidestats');
        }
        if (xarModUserVars::get('modules', 'selmax')) {
            xarModUserVars::delete('modules', 'selmax');
        }
        if (xarModUserVars::get('modules', 'startpage')) {
            xarModUserVars::delete('modules', 'startpage');
        }

        // all done
        return true;
    }
}
