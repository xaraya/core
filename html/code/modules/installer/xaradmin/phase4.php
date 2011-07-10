<?php
/**
 * Installer
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
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
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    // Get default values from config files
    $data['database_host']       = xarSystemVars::get(sys::CONFIG, 'DB.Host');
    $data['database_username']   = xarSystemVars::get(sys::CONFIG, 'DB.UserName');
    $data['database_password']   = '';
    $data['database_name']       = xarSystemVars::get(sys::CONFIG, 'DB.Name');
    $data['database_prefix']     = xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix');
    $data['database_type']       = xarSystemVars::get(sys::CONFIG, 'DB.Type');
    $data['database_charset']    = xarSystemVars::get(sys::CONFIG, 'DB.Charset');

    // Supported  Databases:
    $data['database_types']      = array('mysql'       => array('name' => 'MySQL'   , 'available' => extension_loaded('mysql')),
                                         'postgres'    => array('name' => 'Postgres', 'available' => extension_loaded('pgsql')),
                                         'sqlite'      => array('name' => 'SQLite'  , 'available' => extension_loaded('sqlite')),
                                         //'pdosqlite'   => array('name' => 'PDO SQLite'  , 'available' => extension_loaded('pdo_sqlite')),
                                         // use portable version of OCI8 driver to support ? bind variables
                                         'oci8po'      => array('name' => 'Oracle 9+ (not supported)'  , 'available' => extension_loaded('oci8')),
                                         'mssql'       => array('name' => 'MS SQL Server (not supported)' , 'available' => extension_loaded('mssql')),
                                        );

    $data['language'] = $install_language;
    $data['phase'] = 4;
    $data['phase_label'] = xarML('Step Four');

    return $data;
}

?>