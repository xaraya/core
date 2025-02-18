<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks;

use Xaraya\Modules\AdminApiClass;
use sys;

sys::import('xaraya.modules.adminapi');

/**
 * Handle the blocks admin API
 *
 * @method mixed import(array $args = []) Import a block definition from XML
 * @method mixed registerBlockType(array $args = []) Register block type - IMPORTANT: this function is marked for deprecation - The blocks subsystem now automatically creates block types - when modules are activated
 * @method mixed unregisterBlockType(array $args = []) Unregister block type - IMPORTANT: this function is marked for deprecation - The blocks subsystem now automatically creates block types - when modules are removed
 * @extends AdminApiClass<Module>
 */
class AdminApi extends AdminApiClass
{
    use OtherApiTrait;
    // ...
}
