<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories;

use Xaraya\Modules\AdminApiClass;
use sys;

sys::import('xaraya.modules.adminapi');

/**
 * Handle the modules admin API
 *
 * @method mixed create(array $args = []) Create a new category
 * @method mixed createhook(array $args = []) Create linkage for an item - hook for ('item','create','API') - Needs $extrainfo['cids'] from arguments, or 'cids' from input
 * @method mixed deletehook(array $args = []) Delete linkage for an item - hook for ('item','delete','API')
 * @method mixed findPointOfInsertion(array $args = []) Find the correct point of insertion for a node in Celko�s model for - hierarchical SQL Trees.
 * @method mixed getmenulinks(array $args = []) Utility function pass individual menu items to the main menu
 * @method mixed linkcat(array $args = []) Link items to categories or links each cid in cids to each iid in iids
 * @method mixed removehook(array $args = []) Delete all category links for a module - hook for ('module','remove','API')
 * @method mixed unlink(array $args = []) Delete all links for a specific Item ID
 * @method mixed unlinkcids(array $args = []) Delete all links for a specific module, itemtype and list of cids (e.g. orphan links)
 * @method mixed updatecelkolinks(array $args = []) Updates celko links
 * @method mixed updateconfighook(array $args = []) Update configuration for a module - hook for ('module','updateconfig','API') - Needs $extrainfo['cids'] from arguments, or 'cids' from input
 * @method mixed updatehook(array $args = []) update linkage for an item - hook for ('item','update','API') - Needs $extrainfo['cids'] from arguments, or 'cids' from input
 * @extends AdminApiClass<Module>
 */
class AdminApi extends AdminApiClass
{
    // ...
}
