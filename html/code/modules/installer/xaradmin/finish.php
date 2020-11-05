<?php
/**
 * Installer
 *
 * @package modules\installer\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 */

/* Do not allow this script to run if the install script has been removed.
 * This assumes the install.php and index.php are in the same directory.
 * @author Paul Rosania
 * @author Marcel van der Boom <marcel@hsdev.com>
 */

function installer_admin_finish()
{
    xarVar::fetch('returnurl', 'str', $returnurl, 'site', xarVar::NOT_REQUIRED);

    // Default debug admin @fixme this was just configured by the user, and could be anything...
    $admin = xarMod::apiFunc('roles', 'user', 'get', array('uname' => 'admin'));
    if (!empty($admin) && !empty($admin['id'])) xarConfigVars::set(null, 'Site.User.DebugAdmins', array($admin['id']));

    // Default for the site time zone is the system time zone
    xarConfigVars::set(null, 'Site.Core.TimeZone', xarSystemVars::get(sys::CONFIG, 'SystemTimeZone'));

    // Defaults for templating engine options 
    xarConfigVars::set(null, 'Site.BL.CompressWhitespace', 1);
    xarConfigVars::set(null, 'Site.BL.MemCacheTemplates', false);

    // Default for AJAX calls
    xarConfigVars::set(null, 'Site.Core.AllowAJAX', true);

    // Display variable values in exceptions?
    xarConfigVars::set(null, 'Site.BL.ExceptionDisplay', false);

    // Declare the installation a success
    $variables = array('DB.Installation' => 3);
    xarMod::apiFunc('installer','admin','modifysystemvars', array('variables'=> $variables));
    
    switch ($returnurl) {
        case ('base'):
            xarController::redirect(xarController::URL('base','admin','modifyconfig'));
        case ('modules'):
            xarController::redirect(xarController::URL('modules','admin','list'));
        case ('blocks'):
            xarController::redirect(xarController::URL('blocks','admin','view_instances'));
        case ('site'):
        default:
            xarController::redirect('index.php');
    }
    return true;
}

