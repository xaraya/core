<?php
/**
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * Check for existance of a block type
 *
 * @author Jim McDonald
 * @author Paul Rosania
 * @access public
 * @param array    $args array of optional parameters<br/>
 *        string   $args['modName'] the module name<br/>
 *        string   $args['blockType'] the block type
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM
 * @deprec Deprecated 11 Jan 2004 - use countblocktypes directly
 */
function blocks_adminapi_block_type_exists(Array $args=array())
{
    extract($args);

    if (empty($modName))   throw new EmptyParameterException('modName');
    if (empty($blockType)) throw new EmptyParameterException('blockType');

    $count = xarMod::apiFunc('blocks', 'user', 'countblocktypes', array('module'=>$modName, 'type'=>$blockType));

    return ($count > 0) ? true : false;
}

?>
