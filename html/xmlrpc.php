<?php
// $Id$
// ----------------------------------------------------------------------
// PostNuke Content Management System
// Copyright (C) 2001 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// ----------------------------------------------------------------------
// Original Author of file: Gregor J. Rothfuss
// Purpose of file: XML-RPC server for postnuke
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