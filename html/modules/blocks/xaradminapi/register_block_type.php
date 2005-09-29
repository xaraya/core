<?php
/**
 * Register a block type
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/**
 * Register block type
 *
 * @author Jim McDonald, Paul Rosania
 * @access public
 * @param modName the module name (deprecated)
 * @param blockType the block type (deprecated)
 * @param args['module'] the module name
 * @param args['type'] the block type
 * @returns ID of block type registered (even if already registered)
 * @return true on success, false on failure
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function blocks_adminapi_register_block_type($args)
{
    return xarModAPIfunc('blocks', 'admin', 'create_type', $args);
}

?>