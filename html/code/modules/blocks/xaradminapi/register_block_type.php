<?php
/**
 * Register a block type
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * Register block type
 *
 * @author Jim McDonald
 * @author Paul Rosania
 * @access public
 * @param array    $args array of optional parameters<br/>
 *        string   $args['modName'] the module name (deprecated)<br/>
 *        string   $args['blockType'] the block type (deprecated)<br/>
 *        string   $args['module'] the module name<br/>
 *        string   $args['type'] the block type
 * @return integer ID of block type registered (even if already registered)
 * @return true on success, false on failure
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function blocks_adminapi_register_block_type(Array $args=array())
{
    return true;
    return xarMod::apiFunc('blocks', 'admin', 'create_type', $args);
}

?>
