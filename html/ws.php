<?php
/**
 * File: $Id$
 * 
 * Xaraya WebServices Interface
 * 
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Xaraya WebServices Interface
 * @author Miko 
*/

/**
 * Main WebServices Function
*/
include 'includes/xarCore.php';

function xarWebservicesMain()
{

// TODO: don't load the whole core
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
            xarLogMessage("Could not load XML-RPC server, giving up");
            die('Could not load XML-RPC server');
        }
    }

    elseif ($type == 'soap') {
        $server = xarModAPIFunc('webservices','user','initSOAPServer');
        if (!$server) {
            $fault = new soap_fault( 
                'Server', '', 
                'Unable to start SOAP server', '' 
            ); 
        // TODO: check this
            echo $fault->serialize();
        }
        if ($server) {
            global $HTTP_RAW_POST_DATA;
            $server->service($HTTP_RAW_POST_DATA);
        }

    } elseif (xarServerGetVar('QUERY_STRING') == 'wsdl') {
        header('Location: ' . xarServerGetBaseURL() . 'modules/webservices/xaraya.wsdl');

    } else {
    // TODO: show something nice(r) ?
        echo '<a href="ws.php?wsdl">WSDL</a><br />
<a href="ws.php?type=xmlrpc">XML-RPC Interface</a><br />
<a href="ws.php?type=soap">SOAP Interface</a>';
    }
}

xarWebservicesMain();

xarCore_disposeDebugger();

?>
