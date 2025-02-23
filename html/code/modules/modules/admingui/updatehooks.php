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

sys::import('xaraya.modules.method');

/**
 * modules admin updatehooks function
 * @extends MethodClass<AdminGui>
 */
class UpdatehooksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update hooks by hook module
     * @author Xaraya Development Team
     * @see AdminGui::updatehooks()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('ManageModules')) {
            return;
        }

        if (!xarSec::confirmAuthKey()) {
            //return xarController::badRequest('bad_author', $this->getContext());
        }
        // Curhook contains module name
        xarVar::fetch('curhook', 'str:1:', $curhook);

        $regId = xarMod::getRegID($curhook);
        if (!isset($curhook) || !isset($regId)) {
            $msg = xarML('Invalid hook');
            throw new Exception($msg);
        }

        xarVar::fetch('subjects', 'array', $subjects, null, xarVar::NOT_REQUIRED);



        $data = [];
        // Only update if the module is active.
        $modinfo = xarMod::getInfo($regId);
        if (!empty($modinfo) && xarMod::isAvailable($modinfo['name'])) {
            $data['regid'] = $regId;
            if (!empty($subjects)) {
                $data['subjects'] = $subjects;
            }
            if (!$adminapi->updatehooks($data)) {
                return;
            }
        }

        xarVar::fetch('return_url', 'isset', $return_url, '', xarVar::NOT_REQUIRED);
        if (!empty($return_url)) {
            xarController::redirect($return_url, null, $this->getContext());
        } else {
            xarController::redirect(xarController::URL(
                'modules',
                'admin',
                'hooks',
                ['hook' => $curhook]
            ), null, $this->getContext());
        }
        return true;
    }
}
