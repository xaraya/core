<?php

/**
 * Obtain list of modules (deprecated)
 *
 * @param none
 * @returns array
 * @return array of known modules
 * @raise NO_PERMISSION
 */
function modules_adminapi_list($args)
{
    // Get arguments from argument array
    extract($args);

    // Security Check
	if(!xarSecurityCheck('AdminModules')) return;

    // Obtain information
    if (!isset($state)) $state = '';
    $modList = xarModGetList(array('State' => $state));
    //throw back
    if (!isset($modList)) return;

    return $modList;
}

?>