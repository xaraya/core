<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------

include 'includes/xarCore.php';

function xarXMLRPCMain()
{
    xarCoreInit(XARCORE_SYSTEM_ALL);

    // Load user API for xmlrpc module
    if (!xarModAPILoad('xmlrpc', 'user')) {
        xarCore_die('Could not load xmlrpc module');
    }

    /* create an instance of an xmlrpc server and define the apis we export
    and the mapping to the functions.
    */
    $server = xarModAPIFunc('xmlrpc','user','initServer');
    if (!$server) {
        xarCore_die('Could not load server');
    }
}

xarXMLRPCMain();

xarCore_disposeDebugger();

?>
