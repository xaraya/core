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
        if (!$this->sec()->checkAccess('AdminBlocks')) {
            return;
        }

        $data = [];
        $this->var()->find('phase', $phase, 'str:1:100', 'modify');
        $this->var()->find('tab', $data['tab'], 'str:1:100', 'general');

        $data['module_settings'] = $this->mod()->apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'blocks']);
        $data['module_settings']->setFieldList('items_per_page, use_module_alias, use_module_icons, enable_short_urls');
        $data['module_settings']->getItem();
        switch (strtolower($phase)) {
            case 'modify':
            default:
                $noexceptions = (int) $this->mod()->getVar('noexceptions');
                $data['noexceptions'] = (!isset($noexceptions)) ? 1 : $noexceptions;

                $data['exceptionoptions'] = [
                    ['id' => 1, 'name' => $this->ml('Fail Silently')],
                    ['id' => 0, 'name' => $this->ml('Raise Exception')],
                ];
                break;

            case 'update':
                // Confirm authorisation code
                if (!$this->sec()->confirmAuthKey()) {
                    return $this->ctl()->badRequest('bad_author');
                }
                $isvalid = $data['module_settings']->checkInput();
                if (!$isvalid) {
                    $this->ctl()->getRequest()->msgAjax($data['module_settings']->getInvalids());
                    $data['context'] ??= $this->getContext();
                    return $this->tpl()->module('blocks', 'admin', 'modifyconfig', $data);
                } else {
                    $itemid = $data['module_settings']->updateItem();
                    $this->var()->find('noexceptions', $noexceptions, 'int:0:1', 0);
                    $this->mod()->setVar('noexceptions', $noexceptions);
                    //    $this->ctl()->redirect($this->ctl()->getModuleURL('blocks', 'admin', 'modifyconfig'));
                    //    return true;
                }
                // If this is an AJAX call, end here
                $this->ctl()->getRequest()->exitAjax();
                $this->ctl()->redirect($this->ctl()->getCurrentURL());
                return true;
        }
        return $data;
    }
}
