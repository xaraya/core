<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------

include 'includes/xarCore.php';

function xarWebservicesMain()
{
    xarCoreInit(XARCORE_SYSTEM_ALL);

    // Load user API for xmlrpc module
    if (!xarModAPILoad('webservices', 'user')) {
        xarCore_die('Could not load webservices module');
    }

    /* determine the server type (xml-rpc or soap), then
    create an instance of an that server and define the apis we export
    and the mapping to the functions.
    */
    $type = xarRequestGetVar('type');

    if ($type == 'xmlrpc') {
        $server = xarModAPIFunc('webservices','user','initXMLRPCServer');
        if (!$server) {
            die('Could not load XML-RPC server');
        }
    }

    elseif ($type == 'soap') {
    // TODO: load SOAP server here.

    }
}

xarWebservicesMain();

xarCore_disposeDebugger();

?>
