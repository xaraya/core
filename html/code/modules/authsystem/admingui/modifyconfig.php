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
     * @return array|string|true|void Returns display template data on success else an output string will be returned or true on redirect
     * @see AdminGui::modifyconfig()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminAuthsystem')) {
            return;
        }

        $data = [];
        $this->var()->find('phase', $phase, 'str:1:100', 'modify');
        $this->var()->find('uselockout', $data['uselockout'], 'checkbox', xarModVars::get('authsystem', 'uselockout'));
        $this->var()->find('lockouttime', $data['lockouttime'], 'int:1:', (int) xarModVars::get('authsystem', 'lockouttime'));
        $this->var()->find('lockouttries', $data['lockouttries'], 'int:1:', (int) xarModVars::get('authsystem', 'lockouttries'));
        $this->var()->find('forwarding_page', $data['forwarding_page'], 'str', xarModVars::get('authsystem', 'forwarding_page'));
        $this->var()->find('ask_forward', $data['ask_forward'], 'checkbox', xarModVars::get('authsystem', 'ask_forward'));
        if (!empty($data['forwarding_page'])) {
            $data['forwarding_page'] = $this->var()->prep($data['forwarding_page']);
        }

        $data['module_settings'] = $this->mod()->apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'authsystem']);
        $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls, frontend_page');
        $data['module_settings']->getItem();

        switch (strtolower($phase)) {
            case 'modify':
            default:
                $data = $this->modifyConfig($data);
                break;

            case 'update':
                return $this->updateConfig($data);
        }
        return $data;
    }

    /**
     * Summary of modifyConfig
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public function modifyConfig(array $data)
    {
        return $data;
    }

    /**
     * Summary of updateConfig
     * @param array<mixed> $data
     * @return string|true output display string if invalid data submitted or true on redirect
     */
    public function updateConfig(array $data)
    {
        // Confirm authorisation code. AJAX calls ignore this
        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }
        $isvalid = $data['module_settings']->checkInput();
        if (!$isvalid) {
            // If this is an AJAX call, send back a message (and end)
            $this->ctl()->getRequest()->msgAjax($data['module_settings']->getInvalids());
            // No AJAX, just send the data to the template for display
            return $this->tpl()->module('authsystem', 'admin', 'modifyconfig', $data);
        } else {
            $itemid = $data['module_settings']->updateItem();
        }
        xarModVars::set('authsystem', 'forwarding_page', $data['forwarding_page']);
        xarModVars::set('authsystem', 'ask_forward', $data['ask_forward']);
        xarModVars::set('authsystem', 'uselockout', $data['uselockout']);
        xarModVars::set('authsystem', 'lockouttime', $data['lockouttime']);
        xarModVars::set('authsystem', 'lockouttries', $data['lockouttries']);

        // If this is an AJAX call, end here
        $this->ctl()->getRequest()->exitAjax();
        $this->ctl()->redirect($this->ctl()->getCurrentURL());
        return true;
    }
}
