<?php

/**
 * Return meta data (test only)
 */
function dynamicdata_util_meta($args)
{
// Security Check
    if(!xarSecurityCheck('AdminDynamicData')) return;

    extract($args);

    if (!xarVarFetch('export', 'notempty', $export, 0, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('table', 'notempty', $table, '', XARVAR_NOT_REQUIRED)) {return;}

    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    $data['tables'] = xarModAPIFunc('dynamicdata','util','getmeta',
                                    array('table' => $table));

    $data['export'] = $export;

    xarTplSetPageTemplateName('admin');

    return $data;
}

?>
