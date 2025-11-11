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

/**
 * blocks adminapi register_block_type function
 * @extends MethodClass<AdminApi>
 */
class RegisterBlockTypeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Register block type
     * IMPORTANT: this function is marked for deprecation
     * The blocks subsystem now automatically creates block types
     * when modules are activated
     * @author Jim McDonald
     * @author Paul Rosania
     * @access public
     * @param array<string,mixed> $args array of optional parameters
     * @param string $args ['modName'] the module name (deprecated)
     * @param string $args ['blockType'] the block type (deprecated)
     * @param string $args ['module'] the module name
     * @param string $args ['type'] the block type
     * @return bool true on success, false on failure
     * @see AdminApi::registerBlockType()
     */
    public function __invoke(array $args = [])
    {
        return true;
    }
}
