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
use DataObjectFactory;
use xarController;
use xarSec;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes admin delete_config function
 * @extends MethodClass<AdminGui>
 */
class DeleteConfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see AdminGui::deleteConfig()
     */

    public function __invoke(array $args = [])
    {
        $data = [];
        $this->var()->find('itemid', $data['itemid'], 'int', 0);
        $this->var()->find('confirm', $data['confirm'], 'int', 0);

        $data['object'] = $this->data()->getObject(['name' => 'themes_configurations']);
        $data['object']->getItem(['itemid' => $data['itemid']]);

        // Security
        if (!$data['object']->checkAccess('delete')) {
            return $this->ctl()->forbidden($this->ml('Delete #(1) is forbidden', $data['object']->label));
        }

        if ($data['confirm']) {

            // Check for a valid confirmation key
            if (!$this->sec()->confirmAuthKey()) {
                return;
            }

            // Delete the item
            $item = $data['object']->deleteItem();

            // Jump to the next page
            $this->ctl()->redirect($this->ctl()->getModuleURL('themes', 'admin', 'view_configs'));
            return true;
        }
        return $data;
    }
}
