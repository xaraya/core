<?php
/**
 * Block Functions
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
 * Blocks Functions
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_main()
{

// Security Check
    if(!xarSecurityCheck('EditBlock')) return;

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_instances'));

    // success
    return true;
}

?>