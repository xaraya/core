<?php

/**
 * Export an object definition or an object item to XML
 */
function dynamicdata_utilapi_export($args)
{
    // restricted to DD Admins
// Security Check
	if(!xarSecurityCheck('AdminDynamicData')) return;

    extract($args);

    // TODO: copy from GUI function :)
}

?>