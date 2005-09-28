<?php
/**
 * File: $Id$
 *
 * Expire non-validated accounts or whatever
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
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