<?php
/**
 * File: $Id$
 * 
 * Xaraya WebServices Interface
 * 
 * @package modules
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage webservices
 * @author Miko 
*/


/**
 * Entry point for the webservices module
 *
 * Just here to create a convenient url, the
 * actual work is done in the module, so we
 * are going as fast as we can to the module
 * to avoid redundancy.
 *
 * This script accepts one parameter: type [xmlrpc, soap]
 * with which the protocol is chosed
 *
 * Entry points for client:
 * XMLRPC: http://host.com/ws.php?type=xmlrpc
 * SOAP  : http://host.com/ws.php?type=soap
 */


/**
 * Main WebServices Function
*/
include 'includes/xarCore.php';

function xarWebservicesMain() {

    // TODO: don't load the whole core
    xarCoreInit(XARCORE_SYSTEM_ALL);
    
    /* determine the server type (xml-rpc or soap), then
    create an instance of an that server and define the apis we export
    and the mapping to the functions.
    */
    $type = xarRequestGetVar('type');
    $server=false;
    switch($type) {
    case  'xmlrpc':
        // xmlrpc server does automatic processing directly
        $server=false;
        if (xarModIsAvailable('xmlrpcserver')) {
            $server = xarModAPIFunc('xmlrpcserver','user','initxmlrpcserver');
        }
        if (!$server) {
            xarLogMessage("Could not load XML-RPC server, giving up");
            // Why do we need to die here?
            die('Could not load XML-RPC server');
        } else {
            xarLogMessage("Created XMLRPC server");
        }
        
        break;
    // Trackback with it's mixed spec
    case  'trackback':
        // xmlrpc server does automatic processing directly
        $server=false;
        if (xarModIsAvailable('trackback')) {
            $error = array();
            if (!xarVarFetch('url', 'str:1:', $url, XARVAR_PREP_FOR_DISPLAY)) {
                // Gots to return the proper error reply
                $error['errordata'] = xarML('No URL Supplied');
            }
            xarVarFetch('title', 'str:1', $title, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY);
            xarVarFetch('blog_name', 'str:1', $blogname, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY);
            if (!xarVarFetch('excerpt', 'str:1:255', $excerpt, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_HTML)) {
                // Gots to return the proper error reply
                $error['errordata'] = xarML('Excerpt longer that 255 characters');
            }
            if (!xarVarFetch('id','str:1:',$id, XARVAR_PREP_FOR_DISPLAY)){
                // Gots to return the proper error reply
                $error['errordata'] = xarML('Bad TrackBack URL.');
            }

            $server = xarModAPIFunc('trackback','user','receive',
                                    array('url'     =>  $url,
                                          'title'   =>  $title,
                                          'blogname'=>  $blogname,
                                          'excerpt'  =>  $excerpt,
                                          'id'      =>  $id,
                                          'error'   =>  $error));
        }
        if (!$server) {
            xarLogMessage("Could not load trackback server, giving up");
            // Why do we need to die here?
            die('Could not load trackback server');
        } else {
            xarLogMessage("Created trackback server");
        }
        
        break;
    case 'soap' :
        $server=false;
        if(xarModIsAvailable('soapserver')) {
            $server = xarModAPIFunc('soapserver','user','initsoapserver');
        
            if (!$server) {
                $fault = new soap_fault('Server','','Unable to start SOAP server', ''); 
                // TODO: check this
                echo $fault->serialize();
            }
            // Try to process the request
            if ($server) {
                global $HTTP_RAW_POST_DATA;
                $server->service($HTTP_RAW_POST_DATA);
            }
        }
        break;
    default:
        if (xarServerGetVar('QUERY_STRING') == 'wsdl') {
            // FIXME: for now wsdl description is in soapserver module
            // consider making the webservices module a container for wsdl files (multiple?)
            header('Location: ' . xarServerGetBaseURL() . 'modules/soapserver/xaraya.wsdl');
        } else {
            // TODO: show something nice(r) ?
            echo '<a href="ws.php?wsdl">WSDL</a><br />
<a href="ws.php?type=xmlrpc">XML-RPC Interface</a><br />
<a href="ws.php?type=trackback">Trackback Interface</a><br />
<a href="ws.php?type=soap">SOAP Interface</a>';
        }
    }
}
xarWebservicesMain();
xarCore_disposeDebugger();
?>