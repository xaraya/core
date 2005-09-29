<?php
/**
 * Display form for a new block instance
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/**
 * display form for a new block instance
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_new_instance()
{
    // Security Check
    if (!xarSecurityCheck('AddBlock', 0, 'Instance')) {return;}

    // Can specify block types for a single module.
    xarVarFetch('formodule', 'str:1', $module, NULL, XARVAR_NOT_REQUIRED);

    // Fetch block type list.
    $block_types = xarModAPIfunc(
        'blocks', 'user', 'getallblocktypes',
        array('order' => 'module,type', 'module' => $module)
    );

    // Fetch available block groups.
    $block_groups = xarModAPIfunc(
        'blocks', 'user', 'getallgroups', array('order' => 'name')
    );

    return array(
        'block_types'  => $block_types,
        'block_groups' => $block_groups,
        'createlabel'  => xarML('Create Instance')
    );
}

?>