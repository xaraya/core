<?php
/** 
 * File: $Id$
 *
 * Register block type
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks administration
 * @author Jim McDonald, Paul Rosania
*/
/**
 * Register block type
 *
 * @access public
 * @param modName the module name
 * @param blockType the block type
 * @returns bool
 * @return true on success, false on failure
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function blocks_adminapi_register_block_type($args)
{
    $res = xarModAPIFunc('blocks','admin','block_type_exists',$args);
    if (!isset($res)) return; // throw back

    extract($args);
    if ($res) {
        $msg = xarML('Block type #(1) already exists in the #(2) module', $blockType, $modName);
        xarExceptionSet(XAR_USER_EXCEPTION, 'ALREADY_EXISTS', new DefaultUserException($msg));
        return;
    }

    list ($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $block_types_table = $xartable['block_types'];

    $seq_id = $dbconn->GenId($block_types_table);
    $query = "INSERT INTO $block_types_table (xar_id, xar_module, xar_type) VALUES ('$seq_id', '$modName', '$blockType');";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    return true;
}

?>
