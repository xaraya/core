<?php

/**
 * modify block group
 */
function blocks_admin_modify_group()
{
    if (!xarVarFetch('gid','int:1:',$gid)) return;

    // Security Check
	if(!xarSecurityCheck('EditBlock',0,'Group')) return;

    $group = xarBlockGroupGetInfo($gid);

    $up_arrow_src   = xarTplGetImage('up.gif');
    $down_arrow_src = xarTplGetImage('down.gif');

    return array('group'            => $group,
                 'instance_count'   => count($group['instances']),
                 'up_arrow_src'     => $up_arrow_src,
                 'down_arrow_src'   => $down_arrow_src,
                 'authid'           => xarSecGenAuthKey());

}

?>