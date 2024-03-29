<?php
/**
 * Return static table information
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
 * Return static table information
 */
function dynamicdata_admin_view_static(array $args = [], $context = null)
{
    // Security
    if(!xarSecurity::check('AdminDynamicData')) {
        return;
    }

    if(!xarVar::fetch('module', 'isset', $module, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('module_id', 'isset', $module_id, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('itemtype', 'isset', $itemtype, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('table', 'isset', $table, '', xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('newtable', 'isset', $newtable, '', xarVar::DONT_SET)) {
        return;
    }
    if (!xarVar::fetch('export', 'isset', $export, 0, xarVar::DONT_SET)) {
        return;
    }

    extract($args);

    if (!empty($newtable)) {
        $query = "CREATE TABLE " . $newtable . " (
          id integer unsigned NOT NULL auto_increment,
          PRIMARY KEY  (id))";
        $dbconn = xarDB::getConn();
        $dbconn->Execute($query);
        $table = $newtable;
    }

    $data = [];
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    $static = xarMod::apiFunc(
        'dynamicdata',
        'util',
        'getstatic',
        ['module'   => $module,
        'module_id'    => $module_id,
        'itemtype' => $itemtype,
        'table'    => $table]
    );

    $metas = xarMod::apiFunc('dynamicdata', 'util', 'getmeta');
    $data['tables'] = [];
    foreach ($metas as $name => $value) {
        $data['tables'][] = ['id' => $name, 'name' => $name];
    }
    $data['table'] = $table;

    //debug($static);
    if (!isset($static) || $static == false) {
        $data['tabledata'] = [];
    } else {
        $data['tabledata'] = [];
        foreach ($static as $field) {
            if (preg_match('/^(\w+)\.(\w+)$/', $field['source'], $matches)) {
                $table = $matches[1];
                $data['tabledata'][$table][$field['name']] = $field;
            }
        }
    }

    $data['export'] = $export;
    if(!isset($module_id) || $module_id == 0) {
        $module_id = 182;
    }
    $data['module_id'] = $module_id;
    $modInfo = xarMod::getInfo($module_id);
    $data['module'] = $modInfo['name'];
    $data['itemtype'] = $itemtype;
    $data['authid'] = xarSec::genAuthKey();

    xarTpl::setPageTemplateName('admin');

    return $data;
}
