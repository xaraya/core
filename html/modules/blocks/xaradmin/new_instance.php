<?php
/** 
 * File: $Id$
 *
 * Display form for a new block instance
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @param formodule optional module name to limit block types to specified module
 * @TODO handling of modules with no block types; probably handle that in the template
 *
 * @subpackage Blocks administration
 * @author Jim McDonald, Paul Rosania
*/
/**
 * display form for a new block instance
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