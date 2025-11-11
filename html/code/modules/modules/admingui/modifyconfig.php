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

/**
 * modules admin modifyconfig function
 * @extends MethodClass<AdminGui>
 */
class ModifyconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify the configuration settings of this module
     * Standard GUI function to display and update the configuration settings of the module based on input data.
     * @author Andy Varganov <andyv@xaraya.com>
     * @access public
     * @return mixed data array for the template display or output display string if invalid data submitted
     * @see AdminGui::modifyconfig()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminModules')) {
            return;
        }

        $data = [];
        $this->var()->find('phase', $phase, 'str:1:100', 'modify');
        $this->var()->find('disableoverview', $data['disableoverview'], 'checkbox', (bool) $this->mod()->getVar('disableoverview'));

        $data['module_settings'] = $this->mod()->apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'modules']);
        $data['module_settings']->setFieldList('items_per_page, use_module_alias, use_module_icons, enable_short_urls');
        $data['module_settings']->getItem();
        switch (strtolower($phase)) {
            case 'modify':
            default:
                break;

            case 'update':
                // Confirm authorisation code
                if (!$this->sec()->confirmAuthKey()) {
                    return $this->ctl()->badRequest('bad_author');
                }
                $isvalid = $data['module_settings']->checkInput();
                if (!$isvalid) {
                    $data['context'] ??= $this->getContext();
                    return $this->tpl()->module('modules', 'admin', 'modifyconfig', $data);
                } else {
                    $itemid = $data['module_settings']->updateItem();
                }
                $this->mod()->setVar('disableoverview', $data['disableoverview']);
                break;
        }
        return $data;
    }
}
