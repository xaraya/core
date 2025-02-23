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
 * modules admin activate function
 * @extends MethodClass<AdminGui>
 */
class ActivateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Activate a module
     * @author Xaraya Development Team
     * Loads module admin API and calls the activate
     * function to actually perform the activation,
     * then redirects to the list function with a
     * status message and returns true.
     * @param int id the module id to activate
     * @return bool|string|void true on success, false on failure
     * @see AdminGui::activate()
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

        // Activate
        $activated = $adminapi->activate(['regid' => $id]);

        //throw back
        if (!isset($activated)) {
            return;
        }
        $minfo = xarMod::getInfo($id);
        // set the target location (anchor) to go to within the page
        $target = $minfo['name'];
        if (empty($return_url)) {
            $return_url = xarController::URL('modules', 'admin', 'list', ['state' => 0], null, $target);
        }

        xarController::redirect($return_url, null, $this->getContext());
        return true;
    }
}
