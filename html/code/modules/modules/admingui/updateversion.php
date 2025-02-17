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
use EmptyParameterException;
use xarController;
use xarMod;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules admin updateversion function
 * @extends MethodClass<AdminGui>
 */
class UpdateversionMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update the module version in the database
     * @param int 'regId' the id number of the module to update
     * @return bool|string|void true on success, false on failure
     * @author Xaraya Development Team
     * @see AdminGui::updateversion()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('AdminModules')) {
            return;
        }

        // Get parameters from input
        xarVar::fetch('id', 'int:1', $regId, 0, xarVar::NOT_REQUIRED);
        if (empty($regId)) {
            return xarController::notFound(null, $this->getContext());
        }


        if (!isset($regId)) {
            throw new EmptyParameterException('regid');
        }

        // Pass to API
        $updated = $adminapi->updateversion(['regId' => $regId]);

        if (!isset($updated)) {
            return;
        }

        // Redirect to module list
        xarController::redirect(xarController::URL('modules', 'admin', 'list'), null, $this->getContext());

        return true;
    }
}
