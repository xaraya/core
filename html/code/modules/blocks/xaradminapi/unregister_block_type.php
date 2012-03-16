<?php
/**
 * Unregister block types
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * Unregister block type
 *
 * @author Jim McDonald
 * @author Paul Rosania
 * @access public
 * @param array    $args array of optional parameters<br/>
 *        string   $args['modName'] the module name<br/>
 *        string   $args['blockType'] the block type
 * @return boolean true on success, false on failure
 */
/**
 * IMPORTANT: this function is marked for deprecation
 * The blocks subsystem now automatically creates block types
 * when modules are removed
**/
function blocks_adminapi_unregister_block_type(Array $args=array())
{
    return true;
}
?>