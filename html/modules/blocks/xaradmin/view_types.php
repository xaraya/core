<?php
/** 
 * File: $Id$
 *
 * View block types
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
 * view block types
 */
function blocks_admin_view_types()
{

    // Security Check
	if(!xarSecurityCheck('EditBlock')) return;

    // Load up database
    list($dbconn) = xarDBGetConn();
    $xartable =& xarDBGetTables();
    $block_types_table = $xartable['block_types'];

    $query = "SELECT    xar_id as id,
                        xar_type as type,
                        xar_module as module
              FROM      $block_types_table
              ORDER BY  xar_module ASC,
                        xar_type ASC";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Load up blocks array
    $block_types = array();
    while(!$result->EOF) {
        $block = $result->GetRowAssoc(false);

        // Store module admin URL
        $block['modurl'] = xarModURL($block['module'], 'admin');

        $block_types[] = $block;

        $result->MoveNext();
    }

    return array('block_types' => $block_types);
}

?>