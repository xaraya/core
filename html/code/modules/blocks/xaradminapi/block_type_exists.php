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
 * @param modName the module name
 * @param blockType the block type
 * @returns bool
 * @return true if exists, false if not found
 * @throws DATABASE_ERROR, BAD_PARAM
 * @deprec Deprecated 11 Jan 2004 - use countblocktypes directly
 */
function blocks_adminapi_block_type_exists($args)
{
    extract($args);

    if (empty($modName))   throw new EmptyParameterException('modName');
    if (empty($blockType)) throw new EmptyParameterException('blockType');

    $count = xarMod::apiFunc('blocks', 'user', 'countblocktypes', array('module'=>$modName, 'type'=>$blockType));

    return ($count > 0) ? true : false;
}

?>
