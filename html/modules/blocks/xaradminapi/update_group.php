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
 * @param $args['instance_order'] the new instance sequence
 * @returns bool
 * @return true on success, false on failure
 */
function blocks_adminapi_update_group($args)
{
    // Get arguments from argument array
    extract($args);

    // Security
	if(!xarSecurityCheck('EditBlock',1,'Block',"$name::$id")) return;

    $dbconn =& xarDBGetConn(0);
    $xartable =& xarDBGetTables();
    $block_groups_table = $xartable['block_groups'];
    $block_group_instances_table = $xartable['block_group_instances'];

    $query = "UPDATE $block_groups_table
              SET xar_name='" . xarVarPrepForStore($name) . "',
                  xar_template='" . xarVarPrepForStore($template) . "'
              WHERE xar_id=" . xarVarPrepForStore($id);
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    if (!empty($instance_order)){
        $instance_order = explode('/', $instance_order);

        while (list($position, $instance_id) = each($instance_order)) {
            // added the "+ 1" to $position because array indicies start at 0 and the
            // $position index should start at 1
            $query = "UPDATE $block_group_instances_table
                      SET   xar_position='" . xarVarPrepForStore($position + 1) . "'
                      WHERE xar_instance_id=" . xarVarPrepForStore($instance_id);
            $result =& $dbconn->Execute($query);
            if (!$result) return;

        }
    }

    return true;
}

?>