<?php
/** 
 * File: $Id$
 *
 * View block types
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
 * view block types
 */
function blocks_admin_view_types()
{
    // Security Check
	if (!xarSecurityCheck('EditBlock')) {return;}

    $block_types = xarModAPIfunc(
        'blocks', 'user', 'getallblocktypes', array('order' => 'module,type')
    );

    // Add in some extra details.
    foreach($block_types as $index => $block_type) {
        $block_types[$index]['modurl'] = xarModURL($block_type['module'], 'admin');
        $block_types[$index]['refreshurl'] = xarModURL(
            'blocks', 'admin', 'update_type_info',
            array('modulename'=>$block_type['module'], 'blocktype'=>$block_type['type'])
        );
        $block_types[$index]['info'] = $block_type['info'];
    }

    return array('block_types' => $block_types);
}

?>