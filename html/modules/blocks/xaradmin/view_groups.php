<?php
/** 
 * File: $Id$
 *
 * View block groups
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
 * view block groups
 */
function blocks_admin_view_groups()
{
    // Security Check
	if(!xarSecurityCheck('AdminBlock',0,'Instance')) return;
    $authid = xarSecGenAuthKey();
    // Load up database
    $dbconn =& xarDBGetConn(0);
    $xartable =& xarDBGetTables();
    $block_groups_table = $xartable['block_groups'];

    $query = "SELECT    xar_id as id,
                        xar_name as name,
                        xar_template as template
              FROM      $block_groups_table
              ORDER BY  xar_name ASC";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Load up groups array
    $block_groups = array();
    while(!$result->EOF) {
        $group = $result->GetRowAssoc(false);
        // Get details on current group
        $group = xarModAPIFunc('blocks', 
                               'admin', 
                               'groupgetinfo', array('blockGroupId' => $group['id']));
        $group['membercount'] = count($group['instances']);
        $group['javascript'] = "return xar_base_confirmLink(this, '" . xarML('Delete group') ." : $group[name] ?')";
        $group['deleteurl'] = xarModUrl('blocks', 'admin', 'delete_group', array('gid' => $group['id'], 'authid' => $authid));
        $block_groups[] = $group;

        $result->MoveNext();
    }

    // Include 'confirmlink' JavaScript.
    // TODO: move this to a template widget when available.
    xarModAPIfunc(
        'base', 'javascript', 'modulefile',
        array('module'=>'base', 'filename'=>'confirmlink.js')
    );

    return array('block_groups' => $block_groups);
}

?>