<?php

/**
 * @package modules\installer
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Installer\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Installer\AdminGui;
use Xaraya\Modules\Installer\AdminApi;
use Exception;
use xarConfigVars;
use xarController;
use xarMod;
use xarSystemVars;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * installer admin finish function
 * @extends MethodClass<AdminGui>
 */
class FinishMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Installer
     * @package modules\installer\installer
     * @subpackage installer
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/200.html
     * @see AdminGui::finish()
     */
    public function __invoke(array $args = [])
    {
        xarVar::fetch('returnurl', 'str', $returnurl, 'site', xarVar::NOT_REQUIRED);

        // Default debug admin @fixme this was just configured by the user, and could be anything...
        $admin = xarMod::apiFunc('roles', 'user', 'get', ['uname' => 'admin']);
        if (!empty($admin) && !empty($admin['id'])) {
            xarConfigVars::set(null, 'Site.User.DebugAdmins', [$admin['id']]);
        }

        // Default for the site time zone is the system time zone
        xarConfigVars::set(null, 'Site.Core.TimeZone', xarSystemVars::get(sys::CONFIG, 'SystemTimeZone'));

        // Defaults for templating engine options
        xarConfigVars::set(null, 'Site.BL.CompressWhitespace', 1);
        xarConfigVars::set(null, 'Site.BL.MemCacheTemplates', false);

        // Default for AJAX calls
        xarConfigVars::set(null, 'Site.Core.AllowAJAX', true);

        // Display variable values in exceptions?
        xarConfigVars::set(null, 'Site.BL.ExceptionDisplay', false);

        // Declare the installation a success
        $variables = ['DB.Installation' => 3];
        xarMod::apiFunc('installer', 'admin', 'modifysystemvars', ['variables' => $variables]);

        switch ($returnurl) {
            case ('base'):
                xarController::redirect(xarController::URL('base', 'admin', 'modifyconfig'));
                // no break
            case ('modules'):
                xarController::redirect(xarController::URL('modules', 'admin', 'list'));
                // no break
            case ('blocks'):
                xarController::redirect(xarController::URL('blocks', 'admin', 'view_instances'));
                // no break
            case ('site'):
            default:
                xarController::redirect('index.php');
        }
        return true;
    }
}
