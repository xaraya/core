<?php
/** 
 * File: $Id$
 *
 * Display form for a new block group
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
 * display form for a new block group
 */
function blocks_admin_new_group()
{
// Security Check
	if(!xarSecurityCheck('AddBlock',0,'Instance')) return;

    // Include 'formcheck' JavaScript.
    // TODO: move this to a template widget when available.
    xarModAPIfunc(
        'base', 'javascript', 'modulefile',
        array('module'=>'base', 'filename'=>'formcheck.js')
    );

    return array('createlabel' => xarML('Create Group'),
                 'cancellabel' => xarML('Cancel'));
}

?>
