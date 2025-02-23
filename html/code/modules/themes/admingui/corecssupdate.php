<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\AdminGui;
use xarController;
use xarModVars;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes admin corecssupdate function
 * @extends MethodClass<AdminGui>
 */
class CorecssupdateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Module admin function to update configuration Xaraya core CSS
     * @author AndyV_at_Xaraya_dot_Com
     * @return bool|string|void true on success, false on failure
     * @see AdminGui::corecssupdate()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AdminThemes')) {
            return;
        }

        // Confirm authorisation code
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        // params
        xarVar::fetch('linkoptions', 'str::', $linkoptions, '', xarVar::NOT_REQUIRED);

        // set modvars
        xarModVars::set('themes', 'csslinkoption', $linkoptions);

        xarController::redirect(xarController::URL(
            'themes',
            'admin',
            'cssconfig',
            ['component' => 'core']
        ), null, $this->getContext());
        // Return
        return true;
    }
}
