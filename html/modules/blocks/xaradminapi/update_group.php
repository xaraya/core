<?php
/** 
 * File: $Id$
 *
 * Update attributes of a block group
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
 * update attributes of a block group
 * @param $args['id'] the ID of the group to update
 * @param $args['name'] the new name of the group
 * @param $args['template'] the new default template of the group
 * @param $args['instance_order'] the new instance sequence (array of bid)
 * @returns bool
 * @return true on success, false on failure
 */
function blocks_adminapi_update_group($args)
{
    // Get arguments from argument array
    extract($args);

    // Security.
    // FIXME: this doesn't seem right - it is a block group, not a block instance here.
	if (!xarSecurityCheck('EditBlock', 1, 'Block', "$name::$id")) {return;}

    if (!is_numeric($id)) {return;}

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $block_groups_table =& $xartable['block_groups'];
    $block_group_instances_table =& $xartable['block_group_instances'];

    $query = 'UPDATE ' . $block_groups_table
        . ' SET xar_name = ?, xar_template = ?'
        . ' WHERE xar_id = ?';
    $result =& $dbconn->Execute($query, array($name, $template, $id));
    if (!$result) {return;}

    if (!empty($instance_order)) {
        $position = 1;
        foreach ($instance_order as $instance_id) {
            $query = 'UPDATE ' . $block_group_instances_table
                . ' SET xar_position = ?'
                . ' WHERE xar_instance_id = ?';
            if (is_numeric($instance_id)) {
                $result =& $dbconn->Execute($query, array($position, $instance_id));
                if (!$result) {return;}
            }

            $position += 1;
        }
    }

    return true;
}

?>