<?php
/**
 * Expire non-validated accounts or whatever via Scheduler
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
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