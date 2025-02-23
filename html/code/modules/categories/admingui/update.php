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
use DataObjectFactory;
use xarController;
use xarModVars;
use xarSec;
use xarTpl;
use xarUser;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories admin update function
 * @extends MethodClass<AdminGui>
 */
class UpdateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update item from categories_admin_modify
     * @return bool|string|null Returns true on success, null on failure
     * @see AdminGui::update()
     */
    public function __invoke(array $args = [])
    {
        $data = [];
        //Checkbox work for submit buttons too
        $this->var()->find('itemtype', $itemtype, 'int', 0);
        $this->var()->find('itemid', $data['itemid'], 'int', 0);

        // Support old cids for now
        $this->var()->check('cid', $cid, 'int::', null);
        $data['itemid'] = !empty($data['itemid']) ? $data['itemid'] : $cid;

        // Confirm authorisation code
        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        // Root category cannot be modified except by the site admin
        if (($cid == 1) && (!xarUser::isSiteAdmin())) {
            return $this->ctl()->badRequest('no_privileges');
        }

        //Reverses the order of cids with the 'last children' option:
        //Look at bug #997

        sys::import('modules.dynamicdata.class.objects.factory');
        $data['object'] = $this->data()->getObject(['name' => $this->mod()->getVar('categoriesobject')]);
        $isvalid = $data['object']->checkInput();

        if (!$isvalid) {
            $data['authid'] = $this->sec()->genAuthKey();
            $data['context'] ??= $this->getContext();
            return $this->tpl()->module('categories', 'admin', 'modfiy', $data);
        }

        $itemid = $data['object']->updateItem(['itemid' => $data['itemid']]);
        $this->ctl()->redirect($this->ctl()->getModuleURL('categories', 'admin', 'view'));
        return true;
    }
}
