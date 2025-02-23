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
 * modules admin updateproperties function
 * @extends MethodClass<AdminGui>
 */
class UpdatepropertiesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update a module
     * @author Xaraya Development Team
     * @param array<mixed> $args
     * @var int id the module's registered id
     * @var string newdisplayname the new display name
     * @var bool admincapable the whether the module shows an admin menu
     * @var bool usercapable the whether the module shows a user menu
     * @return mixed true on success, error message on failure
     * @see AdminGui::updateproperties()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('AdminModules')) {
            return;
        }

        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        // Get parameters
        $this->var()->get('id', $regid, 'id');
        $this->var()->check('olddisplayname', $olddisplayname, 'str::');
        $this->var()->check('displayname', $displayname, 'str::');
        $this->var()->check('admincapable', $admincapable);
        $this->var()->check('usercapable', $usercapable);
        $admincapable = isset($admincapable) ? true : false;
        $usercapable = isset($usercapable) ? true : false;

        if (empty($displayname)) {
            $displayname = $olddisplayname;
        }

        // Pass to API
        $updated = $adminapi->updateproperties(['regid' => $regid,
            'admincapable' => $admincapable,
            'usercapable' => $usercapable,
            'displayname' => $displayname]);

        if (!isset($updated)) {
            return;
        }

        $this->var()->check('return_url', $return_url);
        if (!empty($return_url)) {
            xarController::redirect($return_url, null, $this->getContext());
        } else {
            xarController::redirect(xarController::URL('modules', 'admin', 'list'), null, $this->getContext());
        }

        return true;
    }
}
