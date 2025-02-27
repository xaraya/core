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
        $this->var()->check('return_url', $data['return_url']);
        $this->var()->find('itemid', $data['itemid'], 'int', 0);
        $this->var()->find('confirm', $confirm, 'str:1:', '');
        $this->var()->find('newname', $newname, 'str:1:', "");

        // Support old cids for now
        $this->var()->check('cid', $cid, 'int::', null);
        $data['itemid'] = !empty($data['itemid']) ? $data['itemid'] : $cid;

        // Security check
        if (!$this->sec()->check('AddCategories', 1, 'All', "All:$cid")) {
            return;
        }

        // Setting up necessary data.
        sys::import('modules.dynamicdata.class.objects.factory');
        $data['object'] = $this->data()->getObject(['name' => $this->mod()->getVar('categoriesobject')]);
        $data['object']->getItem(['itemid' => $data['itemid']]);

        if ($confirm) {
            $access = $this->sec()->check('', 0, 'All', "All:" . $data['object']->name . ":" . "All", 0, '', 0, 700);

            if (!$access) {
                return $this->ctl()->badRequest('no_privileges');
            }

            $data['name'] = $data['object']->properties['name']->value;
            $this->var()->find('newname', $newname, 'str', "");
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

            $this->ctl()->redirect($this->ctl()->getModuleURL('categories', 'admin', 'view'));
            return true;
        }
        return $data;
    }
}
