<?php
/**
 * Test the email settings
 *
 * @package modules
 * @subpackage mail module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/771.html
 */

/**
 * Test the email settings
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or void on failure
*/
function mail_admin_compose()
{
    // Security Check
    if (!xarSecurityCheck('ManageMail')) return;
    // Generate a one-time authorisation code for this operation
    $data['authid']         = xarSecGenAuthKey();

    // Get the admin email address
    $data['email']  = xarModVars::get('mail', 'adminmail');
    $data['name']   = xarModVars::get('mail', 'adminname');

    // everything else happens in Template for now
    return $data;
}
?>