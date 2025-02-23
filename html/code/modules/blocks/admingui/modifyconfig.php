<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks\AdminGui;

use Xaraya\Modules\Blocks\MethodClass;
use Xaraya\Modules\Blocks\AdminGui;
use Exception;
use xarController;
use xarMod;
use xarModVars;
use xarSec;
use xarSecurity;
use xarServer;
use xarTpl;
use xarVar;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks admin modifyconfig function
 * @extends MethodClass<AdminGui>
 */
class ModifyconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify the configuration settings of this module
     * Standard GUI function to display and update the configuration settings of the module based on input data.
     * @return bool|array|string|void data array for the template display or output display string if invalid data submitted
     * @see AdminGui::modifyconfig()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AdminBlocks')) {
            return;
        }

        $data = [];
        xarVar::fetch('phase', 'str:1:100', $phase, 'modify', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY);
        xarVar::fetch('tab', 'str:1:100', $data['tab'], 'general', xarVar::NOT_REQUIRED);

        $data['module_settings'] = xarMod::apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'blocks']);
        $data['module_settings']->setFieldList('items_per_page, use_module_alias, use_module_icons, enable_short_urls');
        $data['module_settings']->getItem();
        switch (strtolower($phase)) {
            case 'modify':
            default:
                $noexceptions = (int) xarModVars::get('blocks', 'noexceptions');
                $data['noexceptions'] = (!isset($noexceptions)) ? 1 : $noexceptions;

                $data['exceptionoptions'] = [
                    ['id' => 1, 'name' => xarML('Fail Silently')],
                    ['id' => 0, 'name' => xarML('Raise Exception')],
                ];
                break;

            case 'update':
                // Confirm authorisation code
                if (!xarSec::confirmAuthKey()) {
                    return xarController::badRequest('bad_author', $this->getContext());
                }
                $isvalid = $data['module_settings']->checkInput();
                if (!$isvalid) {
                    xarController::getRequest()->msgAjax($data['module_settings']->getInvalids());
                    $data['context'] ??= $this->getContext();
                    return xarTpl::module('blocks', 'admin', 'modifyconfig', $data);
                } else {
                    $itemid = $data['module_settings']->updateItem();
                    xarVar::fetch('noexceptions', 'int:0:1', $noexceptions, 0, xarVar::NOT_REQUIRED);
                    xarModVars::set('blocks', 'noexceptions', $noexceptions);
                    //    xarController::redirect(xarController::URL('blocks', 'admin', 'modifyconfig'), null, $this->getContext());
                    //    return true;
                }
                // If this is an AJAX call, end here
                xarController::getRequest()->exitAjax();
                xarController::redirect(xarServer::getCurrentURL(), null, $this->getContext());
                return true;
        }
        return $data;
    }
}
