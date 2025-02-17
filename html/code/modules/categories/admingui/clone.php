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
use CategoryWorker;
use DataObjectFactory;
use xarController;
use xarModVars;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories admin clone function
 * @extends MethodClass<AdminGui>
 */
class CloneMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Function to modify category
     * @return array|string|bool|void Returns display data array on success, null on failure
     * @see AdminGui::clone()
     */
    public function __invoke(array $args = [])
    {
        $data = [];
        if (!xarVar::fetch('return_url', 'isset', $data['return_url'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('itemid', 'int', $data['itemid'], 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('confirm', 'str:1:', $confirm, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('newname', 'str:1:', $newname, "", xarVar::NOT_REQUIRED)) {
            return;
        }

        // Support old cids for now
        if (!xarVar::fetch('cid', 'int::', $cid, null, xarVar::DONT_SET)) {
            return;
        }
        $data['itemid'] = !empty($data['itemid']) ? $data['itemid'] : $cid;

        // Security check
        if (!xarSecurity::check('AddCategories', 1, 'All', "All:$cid")) {
            return;
        }

        // Setting up necessary data.
        sys::import('modules.dynamicdata.class.objects.factory');
        $data['object'] = DataObjectFactory::getObject(['name' => xarModVars::get('categories', 'categoriesobject')]);
        $data['object']->getItem(['itemid' => $data['itemid']]);

        if ($confirm) {
            $access = xarSecurity::check('', 0, 'All', "All:" . $data['object']->name . ":" . "All", 0, '', 0, 700);

            if (!$access) {
                return xarController::badRequest('no_privileges', $this->getContext());
            }

            $data['name'] = $data['object']->properties['name']->value;
            if (!xarVar::fetch('newname', 'str', $newname, "", xarVar::NOT_REQUIRED)) {
                return;
            }
            if (empty($newname)) {
                $newname = $data['name'] . "_copy";
            }
            if ($newname == $data['name']) {
                $newname = $data['name'] . "_copy";
            }
            $newname = str_ireplace(" ", "_", $newname);

            sys::import('modules.categories.class.worker');
            $worker = new CategoryWorker();
            $toplevel = $worker->appendTree($data['itemid']);

            // Change the name of the top level category we added
            $data['object']->updateItem(['itemid' => $toplevel, 'name' => $newname]);

            xarController::redirect(xarController::URL('categories', 'admin', 'view'), null, $this->getContext());
            return true;
        }
        return $data;
    }
}
