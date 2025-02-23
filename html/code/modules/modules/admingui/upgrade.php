<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminGui;
use Xaraya\Modules\Modules\AdminApi;
use Exception;
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use xarVar;
use sys;
use InstallerTool;

sys::import('xaraya.modules.method');
sys::import('modules.modules.class.installer');

/**
 * modules admin upgrade function
 * @extends MethodClass<AdminGui>
 */
class UpgradeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Upgrade a module
     * Loads module admin API and calls the upgrade function
     * to actually perform the upgrade, then redrects to
     * the list function and with a status message and returns
     * true.
     * @author Xaraya Development Team
     * @param int id the module id to upgrade
     * @return bool|string|void true on success, false on failure
     * @see AdminGui::upgrade()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('AdminModules')) {
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

        $success = true;

        // See if we have lost any modules since last generation
        sys::import('modules.modules.class.installer');
        $installer = InstallerTool::getInstance();
        if (!$installer->checkformissing()) {
            return;
        }

        // TODO: give the user the opportunity to upgrade the dependancies automatically.
        try {
            $installer->verifydependency($id);
            $minfo = xarMod::getInfo($id);
            //Bail if we've lost our module
            if ($minfo['state'] != xarMod::STATE_MISSING_FROM_UPGRADED) {
                // Upgrade module
                $upgraded = $adminapi->upgrade(['regid' => $id]);
            }
        } catch (Exception $e) {
            // TODO: gradually build up the handling here, for now, bail early.
            throw $e;
        }

        // set the target location (anchor) to go to within the page
        $target = $minfo['name'];
        if (empty($return_url)) {
            $return_url = xarController::URL('modules', 'admin', 'list', ['state' => 0], null, $target);
        }
        // Hmmm, I wonder if the target adding is considered a hack
        // it certainly depends on the implementation of xarController::URL
        //    xarController::redirect(xarController::URL('modules', 'admin', "list#$target"), null, $this->getContext());
        xarController::redirect($return_url, null, $this->getContext());

        return true;
    }
}
