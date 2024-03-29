<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.dynamicdata.class.utilapi');
use Xaraya\DataObject\UtilApi;

/**
 * Return meta data (test only)
 */
function dynamicdata_admin_meta(array $args = [], $context = null)
{
    // Security
    if(!xarSecurity::check('AdminDynamicData')) {
        return;
    }

    extract($args);

    if (!xarVar::fetch('export', 'notempty', $export, '', xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('table', 'notempty', $table, '', xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('showdb', 'notempty', $showdb, 0, xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('dbtype', 'notempty', $dbtype, xarDB::getType(), xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('db', 'notempty', $db, xarDB::getName(), xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('create', 'notempty', $create, '', xarVar::NOT_REQUIRED)) {
        return;
    }

    $data = [];

    $dbconn = xarDB::getConn();
    $dbname = xarDB::getName();

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
            $databases = UtilApi::getDatabases($module);
            if (!empty($databases[$dbname])) {
                $data['dbConnIndex'] = UtilApi::connectDatabase($dbname);
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
        $context
    );

    $data['result'] = '';
    if (!empty($create) && !empty($data['dbConnIndex'])) {
        $data['result'] = UtilApi::importTables($create, $db, $data['dbConnIndex']);
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

    xarTpl::setPageTemplateName('admin');

    return $data;
}
