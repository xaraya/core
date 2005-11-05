<?php
/**
 * Display form for new block group
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/**
 * display form for a new block group
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_new_group()
{
    // Security Check
    if(!xarSecurityCheck('AddBlock', 0, 'Instance')) {return;}

    return array(
        'createlabel' => xarML('Create Group'),
        'cancellabel' => xarML('Cancel')
    );
}

?>
