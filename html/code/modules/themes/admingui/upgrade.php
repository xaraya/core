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
 * themes admin upgrade function
 * @extends MethodClass<AdminGui>
 */
class UpgradeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Upgrade a theme
     * Loads theme admin API and calls the upgrade function
     * to actually perform the upgrade, then redrects to
     * the list function and with a status message and returns
     * true.
     * @author Marty Vance
     * @param int id the theme id to upgrade
     * @return bool|string|void true on success, false on failure
     * @see AdminGui::upgrade()
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

        xarVar::fetch('id', 'int:1:', $id, 0, xarVar::NOT_REQUIRED);
        if (empty($id)) {
            return xarController::notFound(null, $this->getContext());
        }
        xarVar::fetch(
            'return_url',
            'pre:trim:str:1:',
            $return_url,
            '',
            xarVar::NOT_REQUIRED
        );

        // Upgrade theme
        $upgraded = $adminapi->upgrade(['regid' => $id]);

        //throw back
        if (!isset($upgraded)) {
            return;
        }

        if (empty($return_url)) {
            $return_url = xarController::URL('themes', 'admin', 'view');
        }
        xarController::redirect($return_url, null, $this->getContext());
        return true;
    }
}
