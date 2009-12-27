<?php
/**
 * Display form for new block group
 * @package modules
 * @copyright see the html/credits.html file in this release
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
