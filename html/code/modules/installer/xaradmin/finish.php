<?php
/**
 * Installer
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/200.html
 */

/* Do not allow this script to run if the install script has been removed.
 * This assumes the install.php and index.php are in the same directory.
 * @author Paul Rosania
 * @author Marcel van der Boom <marcel@hsdev.com>
 */

function installer_admin_finish()
{
    xarVarFetch('returnurl', 'str', $returnurl, 'site', XARVAR_NOT_REQUIRED);

    // Default debug admin
    $admin = xarMod::apiFunc('roles', 'user', 'get', array('uname' => 'admin'));
    xarConfigVars::set(null, 'Site.User.DebugAdmins', array($admin['id']));

    // Default for the site time zone is the system time zone
    xarConfigVars::set(null, 'Site.Core.TimeZone', xarSystemVars::get(sys::CONFIG, 'SystemTimeZone'));

    // Defaults for templating engine options 
    xarConfigVars::set(null, 'Site.BL.CompressWhitespace', 1);
    xarConfigVars::set(null, 'Site.BL.MemCacheTemplates', false);
    xarConfigVars::set(null, 'Site.BL.DocType', 'xhtml1-strict');

    // Default for AJAX calls
    xarConfigVars::set(null, 'Site.Core.AllowAJAX', true);

    // Declare the installation a success
    $variables = array('DB.Installation' => 3);
    xarMod::apiFunc('installer','admin','modifysystemvars', array('variables'=> $variables));
    
    switch ($returnurl) {
        case ('base'):
            xarController::redirect(xarModURL('base','admin','modifyconfig'));
        case ('modules'):
            xarController::redirect(xarModURL('modules','admin','list'));
        case ('blocks'):
            xarController::redirect(xarModURL('blocks','admin','view_instances'));
        case ('site'):
        default:
            xarController::redirect('index.php');
    }
    return true;
}

?>