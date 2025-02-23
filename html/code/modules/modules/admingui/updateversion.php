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
        if (!$this->sec()->checkAccess('AdminModules')) {
            return;
        }

        // Get parameters from input
        $this->var()->find('id', $regId, 'int:1', 0);
        if (empty($regId)) {
            return $this->ctl()->notFound();
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
        $this->ctl()->redirect($this->ctl()->getModuleURL('modules', 'admin', 'list'));

        return true;
    }
}
