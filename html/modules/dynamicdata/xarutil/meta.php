<?php

/**
 * Return meta data (test only)
 */
function dynamicdata_util_meta($args)
{
// Security Check
	if(!xarSecurityCheck('AdminDynamicData')) return;

    list($export,
         $table) = xarVarCleanFromInput('export',
                                        'table');

    extract($args);
    if (empty($export)) {
        $export = 0;
    }
    if (empty($table)) {
        $table = '';
    }

    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    $data['tables'] = xarModAPIFunc('dynamicdata','util','getmeta',
                                    array('table' => $table));

    $data['export'] = $export;

    xarTplSetPageTemplateName('admin');

    return $data;
}

?>