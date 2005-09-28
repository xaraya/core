<?php
/**
 * File: $Id
 *
 * Call the waiting content hook
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * 
 * @subpackage adminpanels module
 * @author Andy Varganov <andyv@xaraya.com>
*/
/**
 * call the waiting content hook
 *
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @param   none
 * @return  true on success or void on failure
 * @throws  NO_PERMISSION exception
 * @todo    nothing
*/
function adminpanels_adminapi_waitingcontent()
{

    // Hooks (we specify that we want the ones for adminpanels here)
    $output = array();
    $output = xarModCallHooks('item', 'waitingcontent', '', array('module' => 'adminpanels'));

    if (empty($output)) {
        $message = xarML('Waiting Content has not been configured');
    }

    if (empty($message)) {
        $message = '';
    } 

    return array('output'   => $output,
                 'message'  => $message);
}

?>