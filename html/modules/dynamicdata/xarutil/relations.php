<?php

/**
 * Return relationship information (test only)
 */
function dynamicdata_util_relations($args)
{
// Security Check
	if(!xarSecurityCheck('AdminDynamicData')) return;

    if(!xarVarFetch('module',   'isset', $module,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}


    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    // (try to) get the relationships between this module and others
    $data['relations'] = xarModAPIFunc('dynamicdata','util','getrelations',
                                       array('modid' => $modid,
                                             'itemtype' => $itemtype));
    if (!isset($data['relations']) || $data['relations'] == false) {
        $data['relations'] = array();
    }

    xarTplSetPageTemplateName('admin');

    return $data;
}

?>