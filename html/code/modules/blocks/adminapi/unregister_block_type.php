<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks\AdminApi;

use Xaraya\Modules\Blocks\MethodClass;
use Xaraya\Modules\Blocks\AdminApi;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks adminapi unregister_block_type function
 * @extends MethodClass<AdminApi>
 */
class UnregisterBlockTypeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Unregister block type
     * IMPORTANT: this function is marked for deprecation
     * The blocks subsystem now automatically creates block types
     * when modules are removed
     * @author Jim McDonald
     * @author Paul Rosania
     * @access public
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @param string $args ['modName'] the module name<br/>
     * @param string $args ['blockType'] the block type
     * @return bool true on success, false on failure
     * @see AdminApi::unregisterBlockType()
     */
    public function __invoke(array $args = [])
    {
        return true;
    }
}
