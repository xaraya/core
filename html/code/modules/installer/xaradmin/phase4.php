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
 * @return array<mixed> data for the template display
 */
function installer_admin_phase4()
{
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVar::fetch('install_language',            'str::', $install_language, 'en_US.utf-8', xarVar::NOT_REQUIRED);
    xarVar::fetch('continue',            'isset', $continue, NULL, xarVar::NOT_REQUIRED);
    
    xarVar::fetch('install_database_host',       'str::', $data['database_host'],       xarSystemVars::get(sys::CONFIG, 'DB.Host'), xarVar::NOT_REQUIRED);
    xarVar::fetch('install_database_middleware', 'str::', $data['database_middleware'], xarSystemVars::get(sys::CONFIG, 'DB.Middleware'), xarVar::NOT_REQUIRED);
    xarVar::fetch('install_database_type',       'str::', $data['database_type'],       xarSystemVars::get(sys::CONFIG, 'DB.Type'), xarVar::NOT_REQUIRED);
    xarVar::fetch('install_database_name',       'str::', $data['database_name'],       xarSystemVars::get(sys::CONFIG, 'DB.Name'), xarVar::NOT_REQUIRED);
    xarVar::fetch('install_database_username',   'str::', $data['database_username'],   xarSystemVars::get(sys::CONFIG, 'DB.UserName'), xarVar::NOT_REQUIRED);
    xarVar::fetch('install_database_password',   'str::', $data['database_password'],   '', xarVar::NOT_REQUIRED);
    xarVar::fetch('install_database_prefix',     'str::', $data['database_prefix'],     xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix'), xarVar::NOT_REQUIRED);
    xarVar::fetch('install_database_charset',    'str::', $data['database_charset'],    xarSystemVars::get(sys::CONFIG, 'DB.Charset'), xarVar::NOT_REQUIRED);

    // Supported Middleware:
    $data['database_middleware_packages']  = array('Creole' => array('name' => 'Creole', 'available' => true),
                                                   'PDO'    => array('name' => 'PDO',    'available' => extension_loaded('pdo')),
                                                   'DBAL'   => array('name' => 'DBAL',   'available' => false),
                                        );
    // Supported Databases:
    switch ($data['database_middleware']) {
    	case 'Creole':
			$data['database_types']  = array('mysqli'      => array('name' => 'MySQL'   , 'available' => extension_loaded('mysqli')),
											 'pgsql'       => array('name' => 'Postgres (limited support in this version)', 'available' => extension_loaded('pgsql')),
											 'sqlite3'     => array('name' => 'SQLite (not supported in this version)'  , 'available' => false),
											 // use portable version of OCI8 driver to support ? bind variables
											 'oci8po'      => array('name' => 'Oracle 9+ (not supported)'  , 'available' => extension_loaded('oci8')),
											 'mssql'       => array('name' => 'MS SQL Server (not supported)' , 'available' => extension_loaded('mssql')),
											);
		break;
    	case 'PDO':
    	default:
			$data['database_types']  = array('pdomysqli'   => array('name' => 'MySQL'   , 'available' => extension_loaded('pdo_mysql')),
											 'pdopgsql'    => array('name' => 'Postgres (limited support in this version)', 'available' => extension_loaded('pdo_pgsql')),
											 'pdosqlite'   => array('name' => 'PDO SQLite (not supported in this version)'  , 'available' => false),
											);
			break;
    	case 'DBAL':
    		// Nothing yet
			break;
    }

	// The Continue button was clicked
	if (isset($continue)) {
		// Save everything to the configuration file
		$variables['DB.Middleware'] =  $data['database_middleware'];                   
		$variables['DB.Type'] =        $data['database_type'];                   
		$variables['DB.Host'] =        $data['database_host'];                   
		$variables['DB.UserName'] =    $data['database_username'];                   
		$variables['DB.Password'] =    $data['database_password'];                   
		$variables['DB.Name'] =        $data['database_name'];                   
		$variables['DB.TablePrefix'] = $data['database_prefix'];                   
		$variables['DB.Charset'] =     $data['database_charset'];
		xarInstall::apifunc('modifysystemvars', array('variables'=> $variables));
		
		// Jump to the next page
        xarController::redirect(xarServer::getCurrentURL(array('install_phase' => 5)));
        return true;
	}
	
	$data['language'] = $install_language;
    $data['phase'] = 4;
    $data['phase_label'] = xarML('Step Four');

    return $data;
}

