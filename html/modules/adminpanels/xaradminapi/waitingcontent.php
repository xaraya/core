<?php

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
    $output = xarModCallHooks('item', 'waitingcontent', '', array(), 'adminpanels');

    if (empty($output)) {
        $message = xarML('Waiting Content has not been configured');
    } elseif (is_array($output)) {
        $output = join('', $output);
    } 

    if (empty($message)) {
        $message = '';
    } 

    return array('output'   => $output,
                 'message'  => $message);
}

?>
