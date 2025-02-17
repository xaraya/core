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
use Xaraya\Modules\Themes\AdminApi;
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes admin regenerate function
 * @extends MethodClass<AdminGui>
 */
class RegenerateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Regenerate list of available themes
     * Loads theme admin API and calls the regenerate function
     * to actually perform the regeneration, then redirects
     * to the list function with a status meessage and returns true.
     * @author Marty Vance
     * @access public
     * @return bool|string|void true on success, false on failure
     * @see AdminGui::regenerate()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('AdminThemes')) {
            return;
        }

        // Security check
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }
        // Regenerate themes
        $regenerated = $adminapi->regenerate();

        if (!isset($regenerated)) {
            return;
        }
        // Redirect
        xarController::redirect(xarController::URL('themes', 'admin', 'view'), null, $this->getContext());
        return true;
    }
}
