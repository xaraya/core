<?php

/**
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Authsystem\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Authsystem\AdminGui;
use xarController;
use xarMod;
use xarModVars;
use xarSec;
use xarSecurity;
use xarServer;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * authsystem admin modifyconfig function
 * @extends MethodClass<AdminGui>
 */
class ModifyconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify the configuration settings of this module
     * Standard GUI function to display and update the configuration settings of the module based on input data.
     * @return array|string|void Returns display template data on success else an output string will be returned.
     * @see AdminGui::modifyconfig()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AdminAuthsystem')) {
            return;
        }

        $data = [];
        xarVar::fetch('phase', 'str:1:100', $phase, 'modify', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY);
        xarVar::fetch('uselockout', 'checkbox', $data['uselockout'], xarModVars::get('authsystem', 'uselockout'), xarVar::NOT_REQUIRED);
        xarVar::fetch('lockouttime', 'int:1:', $data['lockouttime'], (int) xarModVars::get('authsystem', 'lockouttime'), xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY);
        xarVar::fetch('lockouttries', 'int:1:', $data['lockouttries'], (int) xarModVars::get('authsystem', 'lockouttries'), xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY);
        xarVar::fetch('forwarding_page', 'str', $data['forwarding_page'], xarModVars::get('authsystem', 'forwarding_page'), xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY);
        xarVar::fetch('ask_forward', 'checkbox', $data['ask_forward'], xarModVars::get('authsystem', 'ask_forward'), xarVar::NOT_REQUIRED);

        $data['module_settings'] = xarMod::apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'authsystem']);
        $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls, frontend_page');
        $data['module_settings']->getItem();

        switch (strtolower($phase)) {
            case 'modify':
            default:
                break;

            case 'update':
                // Confirm authorisation code. AJAX calls ignore this
                if (!xarSec::confirmAuthKey()) {
                    return xarController::badRequest('bad_author', $this->getContext());
                }
                $isvalid = $data['module_settings']->checkInput();
                if (!$isvalid) {
                    // If this is an AJAX call, send back a message (and end)
                    xarController::getRequest()->msgAjax($data['module_settings']->getInvalids());
                    // No AJAX, just send the data to the template for display
                    return xarTpl::module('authsystem', 'admin', 'modifyconfig', $data);
                } else {
                    $itemid = $data['module_settings']->updateItem();
                }
                xarModVars::set('authsystem', 'forwarding_page', $data['forwarding_page']);
                xarModVars::set('authsystem', 'ask_forward', $data['ask_forward']);
                xarModVars::set('authsystem', 'uselockout', $data['uselockout']);
                xarModVars::set('authsystem', 'lockouttime', $data['lockouttime']);
                xarModVars::set('authsystem', 'lockouttries', $data['lockouttries']);

                // If this is an AJAX call, end here
                xarController::getRequest()->exitAjax();
                xarController::redirect(xarServer::getCurrentURL(), null, $this->getContext());
                return true;
        }
        return $data;
    }
}
