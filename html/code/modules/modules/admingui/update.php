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
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules admin update function
 * @extends MethodClass<AdminGui>
 */
class UpdateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update a module
     * @author Xaraya Development Team
     * @param int id the module's registered id
     * @param string newdisplayname the new display name
     * @param string newdescription the new description
     * @return mixed true on success, error message on failure
     * @see AdminGui::update()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('EditModules')) {
            return;
        }

        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        // Get parameters
        xarVar::fetch('id', 'id', $regId);
        // CHECKME: what's this?
        xarVar::fetch('newdisplayname', 'str::', $newDisplayName);

        // update hooks...
        if (!xarVar::fetch('observers', 'array', $observers, [], xarVar::NOT_REQUIRED)) {
            return;
        }

        if (!$adminapi->update([
            'regid' => $regId,
            'displayname' => $newDisplayName,
            'observers' => $observers,
        ])) {
            return;
        }

        xarVar::fetch('return_url', 'isset', $return_url, null, xarVar::DONT_SET);
        if (!empty($return_url)) {
            xarController::redirect($return_url, null, $this->getContext());
        } else {
            xarController::redirect(xarController::URL('modules', 'admin', 'modify', ['id' => $regId]), null, $this->getContext());
        }

        return true;
    }
}
