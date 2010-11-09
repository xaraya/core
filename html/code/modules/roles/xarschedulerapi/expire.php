<?php
/**
 * Expire non-validated accounts or whatever via Scheduler
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * expire non-validated accounts or whatever (executed by the scheduler module)
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access private
 */
function roles_schedulerapi_expire(Array $args=array())
{

// TODO: get some configuration info here if necessary
    // $whatever = xarModVars::get('roles','whatever');
    // ...
// TODO: we need some API function here (not a GUI function)
//       It may return true (or some logging text) if it succeeds, and null if it fails
    // return xarMod::apiFunc('roles','admin','...',
    //                      array('whatever' => $whatever));

    return true;
}

?>
