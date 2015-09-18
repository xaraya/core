<?php
/**
 * Unregister block types
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/13.html
 */
/**
 * Unregister block type
 *
 * IMPORTANT: this function is marked for deprecation
 * The blocks subsystem now automatically creates block types
 * when modules are removed
 * 
 * @author Jim McDonald
 * @author Paul Rosania
 * @access public
 * @param array    $args array of optional parameters<br/>
 * @param string   $args['modName'] the module name<br/>
 * @param string   $args['blockType'] the block type
 * @return boolean true on success, false on failure
 */
function blocks_adminapi_unregister_block_type(Array $args=array())
{
    return true;
}
?>