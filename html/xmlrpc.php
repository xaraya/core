<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------

include 'includes/pnCore.php';

function pnXMLRPCMain()
{
    pnCoreInit(PNCORE_SYSTEM_ALL);

    // Load user API for xmlrpc module
    if (!pnModAPILoad('xmlrpc', 'user')) {
        die('Could not load xmlrpc module');
    }

    /* create an instance of an xmlrpc server and define the apis we export
    and the mapping to the functions.
    */
    $server = pnModAPIFunc('xmlrpc','user','initServer');
    if (!$server) {
        die('Could not load server');
    }
}

pnXMLRPCMain();

pnCore_disposeDebugger();

?>