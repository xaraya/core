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

/**
 * Phase 4: Database Settings Page
 *
 * @access private
 * @return array data for the template display
 */
function installer_admin_phase4()
{
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVar::fetch('install_language','str::',$install_language, 'en_US.utf-8', xarVar::NOT_REQUIRED);

    // Get default values from config files
    $data['database_host']       = xarSystemVars::get(sys::CONFIG, 'DB.Host');
    $data['database_username']   = xarSystemVars::get(sys::CONFIG, 'DB.UserName');
    $data['database_password']   = '';
    $data['database_name']       = xarSystemVars::get(sys::CONFIG, 'DB.Name');
    $data['database_prefix']     = xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix');
    $data['database_type']       = xarSystemVars::get(sys::CONFIG, 'DB.Type');
    $data['database_charset']    = xarSystemVars::get(sys::CONFIG, 'DB.Charset');

    // Supported  Databases:
    $data['database_types']      = array('mysqli'      => array('name' => 'MySQL'   , 'available' => extension_loaded('mysqli')),
                                         //'mysqli'      => array('name' => 'MySQLi' , 'available' => extension_loaded('mysqli')),
                                         'postgres'    => array('name' => 'Postgres (not supported in this version)', 'available' => extension_loaded('pgsql')),
                                         //'pdosqlite'   => array('name' => 'PDO SQLite'  , 'available' => extension_loaded('pdo_sqlite')),
                                         // use portable version of OCI8 driver to support ? bind variables
                                         'oci8po'      => array('name' => 'Oracle 9+ (not supported)'  , 'available' => extension_loaded('oci8')),
                                         'mssql'       => array('name' => 'MS SQL Server (not supported)' , 'available' => extension_loaded('mssql')),
                                        );
    if (version_compare(PHP_VERSION,'5.4.0','ge')) {
        $data['database_types']['sqlite3'] = array('name' => 'SQLite3 (not supported in this version)'  , 'available' => extension_loaded('sqlite3'));
    } else {
        $data['database_types']['sqlite']  = array('name' => 'SQLite (not supported in this version)'  , 'available' => extension_loaded('sqlite'));
    }

    $data['language'] = $install_language;
    $data['phase'] = 4;
    $data['phase_label'] = xarML('Step Four');

    return $data;
}