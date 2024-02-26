<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 * @author Marc Lutolf <mfl@netspan.ch>
 */

/**
 * Function return the database types give a middleware
 * 
 * @param string $args['middleware'] Name of the chosen middleware
 * 
 * @return array Returns a dropdown array of the databases supported by the middleware
 */
function base_adminapi_get_supported_dbs($args)
{
    if (empty($args['database_middleware'])) return array();
    
    switch ($args['database_middleware']) {
    	case 'Creole':
			$data['database_types']  = array('mysqli'      => array('name' => 'MySQL'   , 'available' => extension_loaded('mysqli')),
//											 'pgsql'       => array('name' => 'Postgres (limited support in this version)', 'available' => extension_loaded('pgsql')),
//											 'sqlite3'     => array('name' => 'SQLite (not supported in this version)'  , 'available' => extension_loaded('sqlite3')),
//											 'pgsql'       => array('name' => 'Postgres (limited support in this version)', 'available' => false),
//											 'sqlite3'     => array('name' => 'SQLite (not supported in this version)'  , 'available' => false),
											 // use portable version of OCI8 driver to support ? bind variables
//											 'oci8po'      => array('name' => 'Oracle 9+ (not supported)'  , 'available' => extension_loaded('oci8')),
//											 'mssql'       => array('name' => 'MS SQL Server (not supported)' , 'available' => extension_loaded('mssql')),
											);
		break;
    	case 'PDO':
    	default:
			$data['database_types']  = array('pdomysqli'   => array('name' => 'MySQL'   , 'available' => extension_loaded('pdo_mysql')),
//											 'pdopgsql'    => array('name' => 'Postgres (limited support in this version)', 'available' => extension_loaded('pdo_pgsql')),
//											 'pdosqlite'   => array('name' => 'SQLite (not supported in this version)'  , 'available' => extension_loaded('pdo_sqlite')),
//											 'pdopgsql'    => array('name' => 'Postgres (limited support in this version)', 'available' => false),
//											 'pdosqlite'   => array('name' => 'SQLite (not supported in this version)'  , 'available' => false),
											);
		break;
    	case 'DBAL':
    		// Nothing yet
			$data['database_types']  = array();
		break;
    }
    return $data['database_types'];
}
