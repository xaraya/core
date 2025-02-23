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
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes admin initialise function
 * @extends MethodClass<AdminGui>
 */
class InitialiseMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Initialise a theme
     * Loads theme admin API and calls the initialise
     * function to actually perform the initialisation,
     * then redirects to the list function with a
     * status message and returns true.
     * @author Marty Vance
     * @param int id $ the theme id to initialise
     * @return bool|string|void true on success, false on failure
     * @see AdminGui::initialise()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('AdminThemes')) {
            return;
        }

        // Security and sanity checks
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        $this->var()->find('id', $id, 'int:1:', 0);
        if (empty($id)) {
            return xarController::notFound(null, $this->getContext());
        }

        // Initialise theme
        $initialised = $adminapi->initialise(['regid' => $id]);

        if (!isset($initialised)) {
            return;
        }

        xarController::redirect(xarController::URL('themes', 'admin', 'view'), null, $this->getContext());
        return true;
    }
}
