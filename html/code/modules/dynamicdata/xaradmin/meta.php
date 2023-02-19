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
/**
 * Return meta data (test only)
 */
function dynamicdata_admin_meta(Array $args=array())
{
    // Security
    if(!xarSecurity::check('AdminDynamicData')) return;

    extract($args);

    if (!xarVar::fetch('export', 'notempty', $export, '', xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('table', 'notempty', $table, '', xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('showdb', 'notempty', $showdb, 0, xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('db', 'notempty', $db, xarDB::getName(), xarVar::NOT_REQUIRED)) {return;}

    $data = array();

    $dbconn = xarDB::getConn();
    $dbtype = xarDB::getType();
    $dbname = xarDB::getName();

    if ($db != $dbname) {
        $data['db'] = $db;
    } elseif (!empty($table) && strpos($table,'.') !== false) {
        list($data['db'],$other) = explode('.', $table);
    } else {
        $data['db'] = $dbname;
    }

    $data['databases'] = array();

    if (!empty($showdb) || $data['db'] != $dbname) {
        // Note: not supported for other database types
        if ($dbtype == 'mysql') {
            try {
                // Note: this only works if we use the same database connection
                $db_list = mysql_list_dbs($dbconn->getResource());
                while ($row = mysql_fetch_object($db_list)) {
                    $database = $row->Database;
                    $data['databases'][$database] = $database;
                }
            } catch (Exception $e) {
            }
        }

        if (empty($data['databases'])) {
            $data['databases'] = array($db => $db);
        }
    }
    $data['tables'] = xarMod::apiFunc('dynamicdata','util','getmeta',
                                  array('db' => $db, 'table' => $table));

    if ($export == 'ddl') {
        $dbInfo = $dbconn->getDatabaseInfo();
        $data['schemaName'] = $db;

        $data['tables'] = array();
        if (empty($table)) {
            $data['tables'] = $dbInfo->getTables();
        } else {
            $data['tables'] = array($dbInfo->getTable($table));
        }
        $data['types']  = xarDB::getTypeMap();
        $data['xml'] = xarTpl::file(sys::code() . 'modules/dynamicdata/xartemplates/includes/exportddl.xt', $data);
    }

    $data['table'] = $table;
    $data['export'] = $export;
    $data['prop'] = xarMod::apiFunc('dynamicdata','user','getproperty',array('type' => 'fieldtype', 'name' => 'dummy'));

    // Get the default property types
    $proptypes = DataPropertyMaster::getPropertyTypes();
    $proptypenames = array();
    foreach ($proptypes as $proptype) {
        $proptypenames[$proptype['id']] = $proptype['name'];
    }
    $data['proptypes'] = $proptypenames;

    xarTpl::setPageTemplateName('admin');

    return $data;
}