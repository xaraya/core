<?php

/**
 * Return static table information (test only)
 */
function dynamicdata_util_static($args)
{
// Security Check
	if(!xarSecurityCheck('AdminDynamicData')) return;

    if(!xarVarFetch('module',   'isset', $module,    NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,     NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_NOT_REQUIRED)) {return;}


    $export = xarVarCleanFromInput('export');

    extract($args);
    if (empty($export)) {
        $export = 0;
    }

    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    $static = xarModAPIFunc('dynamicdata','util','getstatic',
                            array('module'   => $module,
                                  'modid'    => $modid,
                                  'itemtype' => $itemtype,
                                  'table'    => $table));

    if (!isset($static) || $static == false) {
        $data['tables'] = array();
    } else {
        $data['tables'] = array();
        foreach ($static as $field) {
            if (preg_match('/^(\w+)\.(\w+)$/', $field['source'], $matches)) {
                $table = $matches[1];
                $data['tables'][$table][$field['name']] = $field;
            }
        }
    }

    $data['export'] = $export;
    $data['modid'] = $modid;
    $data['itemtype'] = $itemtype;
    $data['authid'] = xarSecGenAuthKey();

    xarTplSetPageTemplateName('admin');

    return $data;
}

?>