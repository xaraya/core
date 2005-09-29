<?php
/**
 * Modify Block group
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/**
 * modify block group
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_modify_group()
{
    if (!xarVarFetch('gid', 'int:1:', $gid)) {return;}

    // Security Check
    if(!xarSecurityCheck('EditBlock', 0, 'Group')) {return;}

    // Get details on current group
    $group = xarModAPIFunc(
        'blocks', 'user', 'groupgetinfo',
        array('gid' => $gid)
    );

    $up_arrow_src   = xarTplGetImage('up.gif');
    $down_arrow_src = xarTplGetImage('down.gif');

    return array(
        'group'            => $group,
        'instance_count'   => count($group['instances']),
        'up_arrow_src'     => $up_arrow_src,
        'down_arrow_src'   => $down_arrow_src,
        'authid'           => xarSecGenAuthKey(),
        'moveuplabel'      => xarML('Move selected instance up'),
        'movedownlabel'    => xarML('Move selected instance down'),
        'updatelabel'      => xarML('Update')
    );
}

?>