<?php

/**
 * Return relationship information (test only)
 */
function dynamicdata_util_relations($args)
{
// Security Check
	if(!xarSecurityCheck('AdminDynamicData')) return;

    if(!xarVarFetch('module',   'isset', $module,    , XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,     , XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  , XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     , XARVAR_NOT_REQUIRED)) {return;}


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