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
 * @return array<mixed> data for the template display
 */

function installer_admin_security()
{
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVar::fetch('install_language','str::',$install_language, 'en_US.utf-8', xarVar::NOT_REQUIRED);
    xarTpl::setThemeName('installer');
    $data['language']    = $install_language;
    $data['phase'] = 7;
    $data['phase_label'] = xarML('Security Considerations');

    return $data;
}
