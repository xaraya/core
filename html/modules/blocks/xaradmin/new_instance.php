<?php
/** 
 * File: $Id$
 *
 * Display form for a new block instance
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
 * display form for a new block instance
 */
function blocks_admin_new_instance()
{
    // Security Check
	if (!xarSecurityCheck('AddBlock', 0, 'Instance')) {return;}

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    // Fetch block type list
    // TODO: move to API function.
    $block_types_table = $xartable['block_types'];
    $query = "SELECT xar_id as id, xar_type as type, xar_module as module FROM $block_types_table";
    $result =& $dbconn->Execute($query);
    if (!$result) {return;}

    $block_types = array();
    while(!$result->EOF) {
        $block = $result->GetRowAssoc(false);

        $block_types[$block['module'] . ':' . $block['type']] = $block;

        $result->MoveNext();
    }
    ksort($block_types);

    // Position
    // Fetch block type list.
    // TODO: move to API function.
    $block_groups_table = $xartable['block_groups'];
    $query = "SELECT xar_id as id, xar_name as name FROM $block_groups_table";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $block_groups = array();
    while(!$result->EOF) {
        $group = $result->GetRowAssoc(false);

        $block_groups[] = $group;

        $result->MoveNext();
    }

    return array(
        'block_types'  => $block_types,
        'block_groups' => $block_groups,
        'createlabel'  => xarML('Create Instance')
    );
}

?>