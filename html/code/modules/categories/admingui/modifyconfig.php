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
        if (!$this->sec()->checkAccess('AdminCategories')) {
            return;
        }
        $data = [];
        $this->var()->find('phase', $phase, 'str:1:100', 'modify');
        $this->var()->find('tab', $data['tab'], 'str:1:100', 'general');
        $this->var()->find('tabmodule', $tabmodule, 'str:1:100', 'categories');

        $data['module_settings'] = $this->mod()->apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'categories']);
        $data['module_settings']->setFieldList('items_per_page, use_module_alias, use_module_icons, enable_short_urls');
        $data['module_settings']->getItem();

        $regid = $this->mod()->getRegID($tabmodule);
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
                if (!$this->sec()->confirmAuthKey()) {
                    return $this->ctl()->badRequest('bad_author');
                }
                $this->var()->find('usejsdisplay', $usejsdisplay, 'checkbox', xarModVars::get('categories', 'usejsdisplay'));
                $this->var()->find('numstats', $numstats, 'int', xarModVars::get('categories', 'numstats'));
                $this->var()->find('showtitle', $showtitle, 'checkbox', xarModVars::get('categories', 'showtitle'));
                $this->var()->find('allowbatch', $allowbatch, 'checkbox', xarModVars::get('categories', 'allowbatch'));
                $this->var()->find('categoriesobject', $categoriesobject, 'str', xarModVars::get('categories', 'categoriesobject'));

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
                    return $this->tpl()->module('categories', 'admin', 'modifyconfig', $data);
                } else {
                    $itemid = $data['module_settings']->updateItem();
                }

                $this->ctl()->redirect($this->ctl()->getModuleURL(
                    'categories',
                    'admin',
                    'modifyconfig',
                    ['tabmodule' => $tabmodule, 'tab' => $data['tab']]
                ));
                // Return
                return true;

        }
        $data['tabmodule'] = $tabmodule;
        return $data;
    }
}
