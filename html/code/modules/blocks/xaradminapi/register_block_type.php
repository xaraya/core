<?php
/**
 * Register a block type
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * Register block type
 *
 * IMPORTANT: this function is marked for deprecation
 * The blocks subsystem now automatically creates block types
 * when modules are activated
 * 
 * @author Jim McDonald
 * @author Paul Rosania
 * @access public
 * @param array    $args array of optional parameters
 * @param string   $args['modName'] the module name (deprecated)
 * @param string   $args['blockType'] the block type (deprecated)
 * @param string   $args['module'] the module name
 * @param string   $args['type'] the block type
 * @return boolean true on success, false on failure
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function blocks_adminapi_register_block_type(Array $args=array())
{
    return true;
}
?>