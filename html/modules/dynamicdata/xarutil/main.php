<?php

/**
 * Main menu for utility functions
 */
function dynamicdata_util_main()
{
// Security Check
	if(!xarSecurityCheck('AdminDynamicData')) return;

    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    xarTplSetPageTemplateName('admin');

    return $data;
}

?>