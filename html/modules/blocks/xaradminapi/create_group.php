<?php
/**
 * create a new group
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * create a new group
 * @author Jim McDonald, Paul Rosania
 * @param $args['name'] the group name
 * @param $args['template'] the default block template
 * @returns int
 * @return group id on success, false on failure
 */
function blocks_adminapi_create_group($args)
{
    // Get arguments from argument array
    extract($args);

    if (!isset($template)) {
        $template = '';
    }

    // Argument check
    if ((!isset($name))) {
        $msg = xarML('Empty name in adminapi create group');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    // Security
    if (!xarSecurityCheck('AddBlock', 1, 'Block', "All:$name:All")) {return;}

    // Load up database
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $block_groups_table =& $xartable['block_groups'];

    // Insert group into table
    $nextId = $dbconn->GenId($block_groups_table);
    $query = 'INSERT INTO ' . $block_groups_table
        . ' (xar_id, xar_name, xar_template) VALUES (?, ?, ?)';

    $result =& $dbconn->Execute($query , array($nextId, $name, $template));
    if (!$result) {return;}

    // Get group ID as index of groups table
    $group_id = $dbconn->PO_Insert_ID($block_groups_table, 'xar_id');

    return $group_id;
}

?>