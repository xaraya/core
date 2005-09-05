<?php
/** 
 * File: $Id$
 *
 * Create a new group
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
 * create a new group
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
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__."(".__LINE__."): Missing block group name."));return;
    }

    // Security
    if(!xarSecurityCheck('AddBlock',1,'Block',"All:$name:All")) return;

    // Load up database
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $block_groups_table          = $xartable['block_groups'];

    // TODO: make sure group does not already exist

    // Insert group into table
    $nextId = $dbconn->GenId($block_groups_table);
    $query = "INSERT INTO $block_groups_table (
              xar_id,
              xar_name,
              xar_template)
            VALUES (
              " . xarVarPrepForStore($nextId) . ",
              '" . xarVarPrepForStore($name) . "',
              '" . xarVarPrepForStore($template) . "');";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Get group ID as index of groups table
    $group_id = $dbconn->PO_Insert_ID($block_groups_table, 'xar_id');

    return $group_id;
}

?>