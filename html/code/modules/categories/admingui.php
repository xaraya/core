<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Categories;

use Xaraya\Modules\AdminGuiClass;
use sys;

sys::import('xaraya.modules.admingui');
sys::import('modules.categories.adminapi');

/**
 * Handle the categories admin GUI
 *
 * @method mixed buildTree(array $args = []) Check a Celko tree
 * @method mixed checklinks(array $args = []) Check category links for orphans
 * @method mixed clone(array $args = []) Function to modify category
 * @method mixed create(array $args = []) Create one or more new categories
 * @method mixed delete(array $args = []) Delete a category - This function also shows a count on the number of child categories of the current category
 * @method mixed hooks(array $args = []) Hooks shows the configuration of hooks for other modules
 * @method mixed main(array $args = []) The main administration function - This function redirects to the view categories function
 * @method mixed modify(array $args = []) Function to modify category
 * @method mixed modifyconfig(array $args = []) Function to modify admin configuration
 * @method mixed modifyconfighook(array $args = []) Modify configuration for a module - hook for ('module','modifyconfig','GUI')
 * @method mixed modifyhook(array $args = []) Modify categories for an item - hook for ('item','modify','GUI')
 * @method mixed new(array $args = []) Create new category in admin
 * @method mixed newhook(array $args = []) Select categories for a new item - hook for ('item','new','GUI')
 * @method mixed privileges(array $args = []) Manage definition of instances for privileges (unfinished)
 * @method mixed stats(array $args = []) View statistics about category links
 * @method mixed unlink(array $args = []) Delete category links of module items.
 * @method mixed update(array $args = []) Update item from categories_admin_modify
 * @method mixed view(array $args = []) View admin categories
 * @extends AdminGuiClass<Module>
 */
class AdminGui extends AdminGuiClass
{
    // ...
}
