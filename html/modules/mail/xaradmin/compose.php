<?php
/**
 * File: $Id: s.xaradmin.php 1.28 03/02/08 17:38:40-05:00 John.Cox@mcnabb. $
 * 
 * Mail System
 * 
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html} 
 * @link http://www.xaraya.com
 * @subpackage mail module
 * @author John Cox <admin@dinerminor.com> 
 */

/**
 * Test the email settings
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or void on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function mail_admin_compose()
{
    // Security Check
    if (!xarSecurityCheck('AdminMail')) return; 
    // Generate a one-time authorisation code for this operation
    $data['authid']         = xarSecGenAuthKey(); 
    $data['createlabel']    = xarML('Submit');

    // Include 'formcheck' JavaScript.
    // TODO: move this to a template widget when available.
    xarModAPIfunc(
        'base', 'javascript', 'modulefile',
        array('module'=>'base', 'filename'=>'formcheck.js')
    );

    // Get the admin email address
    $data['email']  = xarModGetVar('mail', 'adminmail');
    $data['name']   = xarModGetVar('mail', 'adminname');
     
    // everything else happens in Template for now
    return $data;
} 
?>
