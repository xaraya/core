<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Categories\AdminGui;
use xarController;
use xarMod;
use xarModVars;
use xarSec;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories admin modifyconfig function
 * @extends MethodClass<AdminGui>
 */
class ModifyconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Function to modify admin configuration
     * @return mixed Returns display data array or true on success, null on failure.
     * @see AdminGui::modifyconfig()
     */
    public function __invoke(array $args = [])
    {
        // Security Check
        if (!xarSecurity::check('AdminCategories')) {
            return;
        }
        $data = [];
        xarVar::fetch('phase', 'str:1:100', $phase, 'modify', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY);
        xarVar::fetch('tab', 'str:1:100', $data['tab'], 'general', xarVar::NOT_REQUIRED);
        xarVar::fetch('tabmodule', 'str:1:100', $tabmodule, 'categories', xarVar::NOT_REQUIRED);

        $data['module_settings'] = xarMod::apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'categories']);
        $data['module_settings']->setFieldList('items_per_page, use_module_alias, use_module_icons, enable_short_urls');
        $data['module_settings']->getItem();

        $regid = xarMod::getRegID($tabmodule);
        switch (strtolower($phase)) {
            case 'modify':
            default:
                switch ($data['tab']) {
                    case 'general':
                        break;
                    case 'categories_hooks':
                        break;
                    default:
                        break;
                }

                break;

            case 'update':
                // Confirm authorisation code
                if (!xarSec::confirmAuthKey()) {
                    return xarController::badRequest('bad_author', $this->getContext());
                }
                xarVar::fetch('usejsdisplay', 'checkbox', $usejsdisplay, xarModVars::get('categories', 'usejsdisplay'), xarVar::NOT_REQUIRED);
                xarVar::fetch('numstats', 'int', $numstats, xarModVars::get('categories', 'numstats'), xarVar::NOT_REQUIRED);
                xarVar::fetch('showtitle', 'checkbox', $showtitle, xarModVars::get('categories', 'showtitle'), xarVar::NOT_REQUIRED);
                xarVar::fetch('allowbatch', 'checkbox', $allowbatch, xarModVars::get('categories', 'allowbatch'), xarVar::NOT_REQUIRED);
                xarVar::fetch('categoriesobject', 'str', $categoriesobject, xarModVars::get('categories', 'categoriesobject'), xarVar::NOT_REQUIRED);

                $modvars = [
                    'usejsdisplay',
                    'numstats',
                    'showtitle',
                    'allowbatch',
                    'categoriesobject',
                ];

                $isvalid = $data['module_settings']->checkInput();
                if (!$isvalid) {
                    $data['context'] ??= $this->getContext();
                    return xarTpl::module('categories', 'admin', 'modifyconfig', $data);
                } else {
                    $itemid = $data['module_settings']->updateItem();
                }

                xarController::redirect(xarController::URL(
                    'categories',
                    'admin',
                    'modifyconfig',
                    ['tabmodule' => $tabmodule, 'tab' => $data['tab']]
                ), null, $this->getContext());
                // Return
                return true;

        }
        $data['tabmodule'] = $tabmodule;
        return $data;
    }
}
