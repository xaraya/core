<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminGui;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminGui;
use Xaraya\Modules\DynamicData\UtilApi;
use Xaraya\Modules\DynamicData\UserApi;
use DataPropertyMaster;
use Exception;
use xarDB;
use xarMod;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata admin meta function
 * @extends MethodClass<AdminGui>
 */
class MetaMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Return meta data (test only)
     * @see AdminGui::meta()
     */
    public function __invoke(array $args = [])
    {
        /** @var UtilApi $utilapi */
        $utilapi = $this->utilapi();
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        extract($args);

        $this->var()->check('export', $export, 'notempty', '');
        $this->var()->check('table', $table, 'notempty', '');
        $this->var()->check('showdb', $showdb, 'notempty', 0);
        $this->var()->check('dbtype', $dbtype, 'notempty', $this->db()->getType());
        $this->var()->check('db', $db, 'notempty', $this->db()->getName());
        $this->var()->check('create', $create, 'notempty', '');

        $data = [];

        $dbconn = $this->db()->getConn();
        $dbname = $this->db()->getName();

        if ($db != $dbname) {
            $data['db'] = $db;
        } elseif (!empty($table) && strpos($table, '.') !== false) {
            [$data['db'], $other] = explode('.', $table);
        } else {
            $data['db'] = $dbname;
        }

        $data['databases'] = [];

        $data['dbtype'] = '';
        $data['dbConnIndex'] = 0;
        $data['dbConnArgs'] = [];
        if (!empty($showdb) || $data['db'] != $dbname) {
            if (strpos($data['db'], '.') !== false) {
                // see dbconfig
                [$module, $dbname] = explode('.', $db . '.');
                $databases = $utilapi->getDatabases($module);
                if (!empty($databases[$dbname])) {
                    $data['dbConnIndex'] = $utilapi->connectDatabase($dbname);
                    $connArgs = $databases[$dbname];
                    $data['dbtype'] = $connArgs['databaseType'] ?? $connArgs['external'];
                    $data['dbConnArgs'] = $connArgs;
                }
            } elseif ($dbtype == 'mysqli') {
                // Note: not supported for other database types
                try {
                    // Note: this only works if we use the same database connection
                    $db_list = mysqli_query($dbconn->getResource(), "SHOW DATABASES");
                    while ($row = mysqli_fetch_object($db_list)) {
                        $database = $row->Database;
                        $data['databases'][$database] = $database;
                    }
                } catch (Exception $e) {
                }
            } elseif ($dbtype == 'sqlite3' && !empty($data['db'])) {
                $data['dbtype'] = $dbtype;
                $connArgs = ['databaseType' => $dbtype, 'databaseName' => $data['db']];
                $conn = $this->db()->newConn($connArgs);
                $data['dbConnIndex'] = $this->db()->getConnIndex();
            }

            if (empty($data['databases'])) {
                $data['databases'] = [$db => $db];
            }
        }
        $data['tables'] = $utilapi->getmeta(['db' => $db,
                'table' => $table,
                'dbConnIndex' => $data['dbConnIndex']]);

        $data['result'] = '';
        if (!empty($create) && !empty($data['dbConnIndex'])) {
            $data['result'] = $utilapi->importTables($create, $db, $data['dbConnIndex']);
            $table = '';
        }

        if ($export == 'ddl') {
            $dbInfo = $dbconn->getDatabaseInfo();
            $data['schemaName'] = $db;

            $data['tables'] = [];
            if (empty($table)) {
                $data['tables'] = $dbInfo->getTables();
            } else {
                $data['tables'] = [$dbInfo->getTable($table)];
            }
            $data['types']  = $this->db()->getTypeMap();
            $data['xml'] = xarTpl::file(sys::code() . 'modules/dynamicdata/xartemplates/includes/exportddl.xt', $data);
        }

        $data['table'] = $table;
        $data['export'] = $export;
        $data['prop'] = $this->prop()->getProperty(['type' => 'fieldtype', 'name' => 'dummy']);

        // Get the default property types
        $proptypes = $this->prop()->getPropertyTypes();
        $proptypenames = [];
        foreach ($proptypes as $proptype) {
            $proptypenames[$proptype['id']] = $proptype['name'];
        }
        $data['proptypes'] = $proptypenames;

        $this->tpl()->setPageTemplateName('admin');

        return $data;
    }
}
