<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Categories\AdminApi;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories adminapi create function
 * @extends MethodClass<AdminApi>
 */
class CreateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Create a new category
     * @see AdminApi::create()
     */
    public function __invoke(array $args = [])
    {
        // Make sure we have all the required values
        if (empty($args['name'])) {
            $args['name'] = $this->ml('New Category');
        }
        // This makes the root category to be the parent of this new one
        if (empty($args['parent_id'])) {
            $args['parent_id'] = 1;
        }
        // This makes the relative position of this category the last child of the parent
        if (empty($args['relative_position'])) {
            $args['relative_position'] = 3;
        }

        sys::import('modules.dynamicdata.class.objects.factory');
        $category = $this->data()->getObject(['name' => 'categories']);
        $id = $category->createItem($args);
        return $id;
    }
}
