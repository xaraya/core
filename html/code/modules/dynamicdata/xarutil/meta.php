<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Return meta data (test only)
 */
function dynamicdata_util_meta(Array $args=array())
{
    // Security
    if(!xarSecurityCheck('AdminDynamicData')) return;

    extract($args);

    if (!xarVarFetch('export', 'notempty', $export, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('table', 'notempty', $table, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('showdb', 'notempty', $showdb, 0, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('db', 'notempty', $db, xarDB::getName(), XARVAR_NOT_REQUIRED)) {return;}

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

    xarTplSetPageTemplateName('admin');

    return $data;
}
?>
