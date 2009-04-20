<?php
/**
 * Main admin function
 *
 * @package modules
 * @copyright (C) copyright-placeholder
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * the main administration function
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 */
function authsystem_admin_main()
{
    // Security Check
    if (!xarSecurityCheck('AdminAuthsystem')) return;
   
    xarResponse::Redirect(xarModURL('authsystem', 'admin', 'modifyconfig'));

    // success
    return true;
}
?>
