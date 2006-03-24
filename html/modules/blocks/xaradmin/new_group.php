<?php
/**
 * Display form for new block group
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
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