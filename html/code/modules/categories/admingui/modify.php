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
use Xaraya\Modules\Categories\UserApi;
use DataObjectFactory;
use xarController;
use xarMod;
use xarModHooks;
use xarModVars;
use xarSecurity;
use xarUser;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories admin modify function
 * @extends MethodClass<AdminGui>
 */
class ModifyMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Function to modify category
     * @return array|string|void Returns display data array on success, null on failure
     * @see AdminGui::modify()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        $data = [];
        $this->var()->check('return_url', $data['return_url']);
        $this->var()->find('itemid', $data['itemid'], 'int', 0);
        $this->var()->find('itemtype', $itemtype, 'int', 2);

        // Support old cids for now
        $this->var()->check('cid', $cid, 'int::', null);
        $data['itemid'] = !empty($data['itemid']) ? $data['itemid'] : $cid;

        // Security check
        if (!xarSecurity::check('EditCategories', 1, 'All', "All:$cid")) {
            return;
        }

        // Root category cannot be modified except by the site admin
        if (($cid == 1) && (!xarUser::isSiteAdmin())) {
            return xarController::badRequest('no_privileges', $this->getContext());
        }

        // Setting up necessary data.
        sys::import('modules.dynamicdata.class.objects.factory');
        $data['object'] = DataObjectFactory::getObject(['name' => xarModVars::get('categories', 'categoriesobject')]);
        $data['object']->getItem(['itemid' => $data['itemid']]);

        $data['category'] = $data['object']->getFieldValues();

        $categories = $userapi->getcat(['cid' => false,
            'eid' => $data['itemid'],
            'getchildren' => true]);

        $catinfo = $data['category'];
        $catinfo['module'] = 'categories';
        $catinfo['itemtype'] = $itemtype;
        $catinfo['itemid'] = $data['itemid'];
        $hooks = xarModHooks::call('item', 'modify', $cid, $catinfo);
        if (empty($hooks)) {
            $data['hooks'] = '';
        } else {
            $data['hooks'] = $hooks;
        }

        $category_Stack =  [];

        foreach ($categories as $key => $category) {
            $categories[$key]['slash_separated'] = '';

            while ((count($category_Stack) > 0) &&
                   ($category_Stack[count($category_Stack) - 1]['indentation'] >= $category['indentation'])
            ) {
                array_pop($category_Stack);
            }

            foreach ($category_Stack as $stack_cat) {
                $categories[$key]['slash_separated'] .= $stack_cat['name'] . '&#160;/&#160;';
            }

            array_push($category_Stack, $category);
            $categories[$key]['slash_separated'] .= $category['name'];
        }

        $data['categories'] = $categories;
        $data['itemtype'] = $itemtype;

        return $data;
    }
}
