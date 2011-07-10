<?php
/**
 * Return static table information
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Return static table information
 */
function dynamicdata_admin_view_static(Array $args=array())
{
    // Security
    if(!xarSecurityCheck('AdminDynamicData')) return;

    if(!xarVarFetch('module',   'isset', $module,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('module_id',    'isset', $module_id,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     '', XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('newtable',    'isset', $newtable,     '', XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('export',  'isset', $export,       0, XARVAR_DONT_SET)) {return;}

    extract($args);

    if (!empty($newtable)) {
        $query = "CREATE TABLE " . $newtable . " (
          id integer unsigned NOT NULL auto_increment,
          PRIMARY KEY  (id))";
        $dbconn = xarDB::getConn();
        $dbconn->Execute($query);
        $table = $newtable;
    }
    
    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    $static = xarMod::apiFunc('dynamicdata','util','getstatic',
                            array('module'   => $module,
                                  'module_id'    => $module_id,
                                  'itemtype' => $itemtype,
                                  'table'    => $table));

    $metas = xarMod::apiFunc('dynamicdata','util','getmeta');
    $data['tables'] = array();
    foreach ($metas as $name => $value) $data['tables'][] = array('id' => $name, 'name' => $name);
    $data['table'] = $table;

    //debug($static);
    if (!isset($static) || $static == false) {
        $data['tabledata'] = array();
    } else {
        $data['tabledata'] = array();
        foreach ($static as $field) {
            if (preg_match('/^(\w+)\.(\w+)$/', $field['source'], $matches)) {
                $table = $matches[1];
                $data['tabledata'][$table][$field['name']] = $field;
            }
        }
    }

    $data['export'] = $export;
    if(!isset($module_id) || $module_id == 0) $module_id = 182;
    $data['module_id'] = $module_id;
    $modInfo = xarMod::getInfo($module_id);
    $data['module'] = $modInfo['name'];
    $data['itemtype'] = $itemtype;
    $data['authid'] = xarSecGenAuthKey();

    xarTplSetPageTemplateName('admin');

    return $data;
}

?>
