<?php

/**
 * expire non-validated accounts or whatever (executed by the scheduler module)
 * 
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access private
 */
function roles_schedulerapi_expire($args)
{

// TODO: get some configuration info here if necessary
    // $whatever = xarModGetVar('roles','whatever');
    // ...
// TODO: we need some API function here (not a GUI function)
//       It may return true (or some logging text) if it succeeds, and null if it fails
    // return xarModAPIFunc('roles','admin','...',
    //                      array('whatever' => $whatever));

    return true;
}

?>
