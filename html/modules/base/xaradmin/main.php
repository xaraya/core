<?php
/**
 * Main admin GUI function
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 * @author Marcel van der Boom
 */

/**
 * Main admin gui function, entry point
 * @author John Robeson
 * @author Greg Allan
 * @return bool true on success of return to sysinfo
 */
function base_admin_main()
{
// Security Check
    if(!xarSecurityCheck('AdminBase')) return;

    xarResponseRedirect(xarModURL('base', 'admin', 'sysinfo'));

    // success
    return true;
}

?>
