<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\DataObject\AdminGui;
use DataPropertyMaster;
use Exception;
use xarDB;
use xarMod;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin meta function
 * @extends MethodClass<AdminGui>
 */
class MetaMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Return meta data (test only)
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        extract($args);

        if (!$this->var()->find('export', $export, 'notempty', '')) {
            return;
        }
        if (!$this->var()->find('table', $table, 'notempty', '')) {
            return;
        }
        if (!$this->var()->find('showdb', $showdb, 'notempty', 0)) {
            return;
        }
        if (!$this->var()->find('dbtype', $dbtype, 'notempty', $this->db()->getType())) {
            return;
        }
        if (!$this->var()->find('db', $db, 'notempty', $this->db()->getName())) {
            return;
        }
        if (!$this->var()->find('create', $create, 'notempty', '')) {
            return;
        }

        $data = [];
        $utilapi = new \Xaraya\DataObject\UtilApi();

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
                $conn = xarDB::newConn($connArgs);
                $data['dbConnIndex'] = xarDB::getConnIndex();
            }

            if (empty($data['databases'])) {
                $data['databases'] = [$db => $db];
            }
        }
        $data['tables'] = xarMod::apiFunc(
            'dynamicdata',
            'util',
            'getmeta',
            ['db' => $db,
                'table' => $table,
                'dbConnIndex' => $data['dbConnIndex']],
            $this->getContext()
        );

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
            $data['types']  = xarDB::getTypeMap();
            $data['xml'] = xarTpl::file(sys::code() . 'modules/dynamicdata/xartemplates/includes/exportddl.xt', $data);
        }

        $data['table'] = $table;
        $data['export'] = $export;
        $data['prop'] = xarMod::apiFunc('dynamicdata', 'user', 'getproperty', ['type' => 'fieldtype', 'name' => 'dummy']);

        // Get the default property types
        $proptypes = DataPropertyMaster::getPropertyTypes();
        $proptypenames = [];
        foreach ($proptypes as $proptype) {
            $proptypenames[$proptype['id']] = $proptype['name'];
        }
        $data['proptypes'] = $proptypenames;

        $this->tpl()->setPageTemplateName('admin');

        return $data;
    }
}
