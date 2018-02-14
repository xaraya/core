<?php
/**
 * Test the email settings
 *
 * @package modules\mail
 * @subpackage mail
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/771.html
 */

/**
 * Test the email settings
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return array data for the template display
*/
function mail_admin_compose()
{
    // Security
    if (!xarSecurityCheck('ManageMail')) return;
    
    // Generate a one-time authorisation code for this operation
    $data['authid']         = xarSecGenAuthKey();

    // Get the admin email address
    $data['email']   = xarModVars::get('mail', 'adminmail');
    $data['name']    = xarModVars::get('mail', 'adminname');

    if (!xarVarFetch('confirm', 'int', $confirm, 0, XARVAR_NOT_REQUIRED)) return;
    
    $data['message'] = '';
    if ($confirm) {
        $data['message'] = xarML('Message sent');
    }
    // everything else happens in the template for now
    return $data;
}
?>
