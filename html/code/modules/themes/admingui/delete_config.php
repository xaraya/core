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
        if (!xarVar::fetch('itemid', 'int', $data['itemid'], 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('confirm', 'int', $data['confirm'], 0, xarVar::NOT_REQUIRED)) {
            return;
        }

        $data['object'] = DataObjectFactory::getObject(['name' => 'themes_configurations']);
        $data['object']->getItem(['itemid' => $data['itemid']]);

        // Security
        if (!$data['object']->checkAccess('delete')) {
            return xarController::forbidden(xarML('Delete #(1) is forbidden', $data['object']->label), $this->getContext());
        }

        if ($data['confirm']) {

            // Check for a valid confirmation key
            if (!xarSec::confirmAuthKey()) {
                return;
            }

            // Delete the item
            $item = $data['object']->deleteItem();

            // Jump to the next page
            xarController::redirect(xarController::URL('themes', 'admin', 'view_configs'), null, $this->getContext());
            return true;
        }
        return $data;
    }
}
