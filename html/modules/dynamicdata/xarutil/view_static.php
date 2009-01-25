<?php
/**
 * Return static table information
 * @package modules
 * @copyright (C) copyright-placeholder
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Return static table information
 */
function dynamicdata_util_view_static($args)
{
    if(!xarSecurityCheck('AdminDynamicData')) return;

    if(!xarVarFetch('module',   'isset', $module,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     '', XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('export',  'isset', $export,       0, XARVAR_DONT_SET)) {return;}

    extract($args);

    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    $static = xarModAPIFunc('dynamicdata','util','getstatic',
                            array('module'   => $module,
                                  'modid'    => $modid,
                                  'itemtype' => $itemtype,
                                  'table'    => $table));

    $metas = xarModAPIFunc('dynamicdata','util','getmeta');
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
    if(!isset($modid) || $modid == 0) $modid = 182;
    $data['modid'] = $modid;
    $modInfo = xarModGetInfo($modid);
    $data['module'] = $modInfo['name'];
    $data['itemtype'] = $itemtype;
    $data['authid'] = xarSecGenAuthKey();

    if (xarModVars::get('themes','usedashboard')) {
        $admin_tpl = xarModVars::get('themes','dashtemplate');
    } else {
       $admin_tpl='default';
    }
    xarTplSetPageTemplateName($admin_tpl);

    return $data;
}

?>
