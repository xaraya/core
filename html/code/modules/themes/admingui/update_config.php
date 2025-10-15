<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\AdminGui;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes admin update_config function
 * @extends MethodClass<AdminGui>
 */
class UpdateConfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see AdminGui::updateConfig()
     */

    public function __invoke(array $args = [])
    {
        if (!$this->sec()->checkAccess('EditThemes')) {
            return;
        }

        $data = [];
        $this->var()->find('itemid', $data['itemid'], 'int', 0);
        $this->var()->find('confirm', $data['confirm'], 'bool', false);
        $this->var()->find('update', $data['update'], 'str', false);

        $data['object'] = $this->data()->getObject(['name' => 'themes_configurations']);
        $data['object']->getItem(['itemid' => $data['itemid']]);

        if ($data['confirm']) {

            // Check for a valid confirmation key
            if (!$this->sec()->confirmAuthKey()) {
                return;
            }

            // Get the data from the form
            $isvalid = $data['object']->checkInput();

            if (!$isvalid) {
                // Bad data: redisplay the form with error messages
                $data['context'] ??= $this->getContext();
                return $this->tpl()->module('themes', 'admin', 'update_config', $data);
            } else {

                // Good data: create the item
                $itemid = $data['object']->updateItem(['itemid' => $data['itemid']]);
                if ($data['update']) {
                    $this->ctl()->redirect($this->ctl()->getModuleURL('themes', 'admin', 'view_configs'));
                    return true;
                } else {
                    $this->ctl()->redirect($this->ctl()->getModuleURL('themes', 'admin', 'update_config', $data));
                    return true;
                }
            }
        }
        return $data;
    }
}
