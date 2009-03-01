<?php
/**
 * Main modules module function
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * main modules module function
 * @return modules_admin_main
 *
 * @author Xaraya Development Team
 */
function modules_admin_main()
{
    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    xarResponseRedirect(xarModURL('modules', 'admin', 'list'));

    // success
    return true;
}

?>
