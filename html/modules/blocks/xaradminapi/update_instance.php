<?php
/** 
 * File: $Id$
 *
 * Update attributes of a block instance
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
 * update attributes of a block instance
 * @param $args['id'] the ID of the block to update
 * @param $args['title'] the new title of the block
 * @param $args['group_id'] the new position of the block
 * @param $args['template'] the new language of the block
 * @param $args['content'] the new content of the block
 * @param $args['refresh'] the new refresh rate of the block
 * @returns bool
 * @return true on success, false on failure
 */
function blocks_adminapi_update_instance($args)
{
    // Get arguments from argument array
    extract($args);

    // Optional arguments
    if (!isset($content)) {
        $content = '';
    }

    if (!isset($template)) {
	$template = '';
    }

    // Argument check
    if ((!isset($bid)) ||
        (!isset($title)) ||
        (!isset($refresh)) ||
        (!isset($group_id)) ||
        (!isset($state))) {
        xarSessionSetVar('errormsg', _MODARGSERROR);
        return false;
    }

    // Security
	if(!xarSecurityCheck('EditBlock',1,'Block',"$title::$bid")) return;

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];

    $query = "UPDATE $block_instances_table
              SET xar_content='" . xarVarPrepForStore($content) . "',
                  xar_template='" . xarVarPrepForStore($template) . "',
                  xar_title='" . xarVarPrepForStore($title) . "',
                  xar_refresh='" . xarVarPrepForStore($refresh) . "',
                  xar_state='" . xarVarPrepForStore($state) . "'
              WHERE xar_id=" . xarVarPrepForStore($id);
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $query = "UPDATE $block_group_instances_table
              SET   xar_group_id='" . xarVarPrepForStore($group_id) . "'
              WHERE xar_instance_id=" . xarVarPrepForStore($id);
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    xarModCallHooks(
                    'item', 'update', $id, ''
                    );
    
    return true;
}

?>
