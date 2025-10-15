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
use sys;

sys::import('xaraya.modules.method');

/**
 * categories admin new function
 * @extends MethodClass<AdminGui>
 */
class NewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Create new category in admin
     * @return array|null Returns display data array on success, null on failure
     * @see AdminGui::new()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        $data = [];
        $this->var()->check('return_url', $data['return_url']);
        $this->var()->find('repeat', $data['repeat'], 'int:1:', 1);

        if (!$this->sec()->checkAccess('AddCategories')) {
            return;
        }

        sys::import('modules.dynamicdata.class.objects.factory');
        for ($i = 1;$i <= $data['repeat'];$i++) {
            $data['objects'][$i] = $this->data()->getObject(['name' => $this->mod()->getVar('categoriesobject'), 'fieldprefix' => $i]);
        }

        // Setting up necessary data.
        $categories = $userapi->getcat(['cid' => false,
            'getchildren' => true]);

        $catinfo = [];
        $catinfo['module'] = 'categories';
        $catinfo['itemid'] = '';
        $hooks = $this->mod()->callHooks('item', 'new', '', $catinfo);
        if (empty($hooks)) {
            $data['hooks'] = '';
        } else {
            $data['hooks'] = $hooks;
        }

        $data['category'] = ['left' => 0,'right' => 0,'name' => '','description' => '', 'image' => ''];
        $data['cid'] = null;

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
        $data['authid'] = $this->sec()->genAuthKey();
        return $data;
    }
}
