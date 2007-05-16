<?php
/**
 * create a new group
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

    if (!isset($template)) $template = '';

    // Argument check
    if ((!isset($name))) throw new EmptyParameterException('name');

    // Security
    if (!xarSecurityCheck('AddBlock', 1, 'Block', "All:$name:All")) {return;}

    // Load up database
    $dbconn = xarDB::getConn();
    $xartable =& xarDBGetTables();
    $block_groups_table =& $xartable['block_groups'];

    // Insert group into table
    $query = 'INSERT INTO ' . $block_groups_table
        . ' (name, template) VALUES (?, ?)';
    $dbconn->Execute($query , array($name, $template));

    // Get group ID as index of groups table
    $group_id = $dbconn->getLastId($block_groups_table);

    return $group_id;
}

?>
