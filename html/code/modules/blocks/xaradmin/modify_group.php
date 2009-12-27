<?php
/**
 * Modify Block group
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * modify block group
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_modify_group()
{
    if (!xarVarFetch('id', 'int:1:', $id)) {return;}

    // Security Check
    if(!xarSecurityCheck('EditBlock', 0, 'Group')) {return;}

    // Get details on current group
    $group = xarMod::apiFunc(
        'blocks', 'user', 'groupgetinfo',
        array('id' => $id)
    );

    $up_arrow_src   = xarTplGetImage('up.gif', 'base');
    $down_arrow_src = xarTplGetImage('down.gif', 'base');
    
    $instances = array();
    foreach ($group['instances'] as $instance) 
        $instances[] = array('id' => $instance['id'], 'name' =>$instance['name'] . " (" . $instance['title'] . ")");

    return array(
        'group'            => $group,
        'instances'        => $instances,
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
