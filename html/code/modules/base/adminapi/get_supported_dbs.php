<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Base\AdminApi;
use sys;

sys::import('xaraya.modules.method');

/**
 * base adminapi get_supported_dbs function
 * @extends MethodClass<AdminApi>
 */
class GetSupportedDbsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Function return the database types give a middleware
     * @param array<string,mixed> $args
     * with $args['database_middleware'] Name of the chosen middleware
     * @return array Returns a dropdown array of the databases supported by the middleware
     * @see AdminApi::getSupportedDbs()
     */
    public function __invoke(array $args = [])
    {
        if (empty($args['database_middleware'])) {
            return [];
        }

        switch ($args['database_middleware']) {
            case 'Creole':
                $data['database_types']  = ['mysqli'      => ['name' => 'MySQL', 'available' => extension_loaded('mysqli')],
                    //											 'pgsql'       => array('name' => 'Postgres (limited support in this version)', 'available' => extension_loaded('pgsql')),
                    'sqlite3'     => ['name' => 'SQLite (limited support in this version)', 'available' => extension_loaded('sqlite3')],
                    //											 'pgsql'       => array('name' => 'Postgres (limited support in this version)', 'available' => false),
                    // use portable version of OCI8 driver to support ? bind variables
                    //											 'oci8po'      => array('name' => 'Oracle 9+ (not supported)'  , 'available' => extension_loaded('oci8')),
                    //											 'mssql'       => array('name' => 'MS SQL Server (not supported)' , 'available' => extension_loaded('mssql')),
                ];
                break;
            case 'PDO':
            default:
                $data['database_types']  = ['pdomysqli'   => ['name' => 'MySQL', 'available' => extension_loaded('pdo_mysql')],
                    //											 'pdopgsql'    => array('name' => 'Postgres (limited support in this version)', 'available' => extension_loaded('pdo_pgsql')),
                    'pdosqlite'   => ['name' => 'SQLite (limited support in this version)', 'available' => extension_loaded('pdo_sqlite')],
                    //											 'pdopgsql'    => array('name' => 'Postgres (limited support in this version)', 'available' => false),
                ];
                break;
            case 'DBAL':
                // Nothing yet
                $data['database_types']  = [];
                break;
        }
        return $data['database_types'];
    }
}
