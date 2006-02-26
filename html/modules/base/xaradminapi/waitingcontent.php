<?php
/**
 * Call the waiting content hook
 *
 * @package modules
 * @copyright (C) 2005-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base module
 * @link http://xaraya.com/index.php/release/30205.html
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
function base_adminapi_waitingcontent()
{

    // Hooks (we specify that we want the ones for adminpanels here)
    $output = array();
    $output = xarModCallHooks('item', 'waitingcontent', '', array('module' => 'base'));

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