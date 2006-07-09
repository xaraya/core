<?php
/**
 * Xaraya WebServices Interface
 * 
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage webservices
 * @author Miko 
*/

/**
 * Entry point for webservices 
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
 * XMLRPC        : http://host.com/ws.php?type=xmlrpc
 * SOAP          : http://host.com/ws.php?type=soap
 * TRACKBACK     : http://host.com/ws.php?type=trackback (Is this still right?)
 * WEBDAV        : http://host.com/ws.php?type=webdav
 * FLASHREMOTING : http://host.com/ws.php?type=flashremoting
 */

/**
 * Main WebServices Function
 *
 * @access public
 * @todo make this a bit more structured, so all the services have roughly the same interface
 * @toco provide ws.php as a nice (templated) page instead of the dumb list of links
*/
include 'includes/xarCore.php';

function xarWebservicesMain() 
{

    // TODO: don't load the whole core
    xarCoreInit(XARCORE_SYSTEM_ALL);
    
    /* 
     determine the server type, then
     create an instance of an that server and 
     serve the request according the ther servers protocol
    */
    xarVarFetch('type','enum:xmlrpc:trackback:soap:webdav:flashremoting',$type,'');
    xarLogMessage("In webservices with type=$type");
    $server=false;
    switch($type) {
    case  'xmlrpc':
        // xmlrpc server does automatic processing directly
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
    // Hmmm, this seems a bit of a strange duck in this place here.
    // Trackback with it's mixed spec. i.e. not an xml formatted request, but a simple POST
    // It doesnt mean however we can't treat the thing the same, ergo move the specifics out of here
    case  'trackback':
        if (xarModIsAvailable('trackback')) {
            $error = array();
            if (!xarVarFetch('url', 'str:1:', $url)) {
                // Gots to return the proper error reply
                $error['errordata'] = xarML('No URL Supplied');
            }
            // These are the specifics ;-)
            xarVarFetch('title', 'str:1', $title, '', XARVAR_NOT_REQUIRED);
            xarVarFetch('blog_name', 'str:1', $blogname, '', XARVAR_NOT_REQUIRED);
            if (!xarVarFetch('excerpt', 'str:1:255', $excerpt, '', XARVAR_NOT_REQUIRED)) {
                // Gots to return the proper error reply
                $error['errordata'] = xarML('Excerpt longer that 255 characters');
            }
            if (!xarVarFetch('id','str:1:',$id)){
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
        if(xarModIsAvailable('soapserver')) {
            $server = xarModAPIFunc('soapserver','user','initsoapserver');
        
            if (!$server) {
                // erm, where does this one come from? lucky because we did the api func?
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
    case 'webdav' :
        xarLogMessage("WebDAV request");
        if(xarModIsAvailable('webdavserver')) {
            $server = xarModAPIFunc('webdavserver','user','initwebdavserver');
            if(!$server) {
                xarLogMessage('Could not load webdav server, giving up');
                // FIXME: construct errors response manually? bah
                die('Could not load webdav server');
            } else {
                xarLogMessage("Created webdav server");
            }
            $server->ServeRequest();
        }
        break;
      case 'flashremoting' :
          xarLogMessage("FlashRemoting request");
        if(xarModIsAvailable('flashservices')) {
          $server = xarModAPIFunc('flashservices','user','initflashservices');
          if (is_object($server)) {
              $server->service();
              
          } else {
            echo "could not create flashremoting server";
            
          }// if
        }// if
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
<a href="ws.php?type=soap">SOAP Interface</a><br/>
<a href="ws.php?type=webdav">WebDAV Interface</a><br/>
<a href="ws.php?type=flashremoting">FLASHREMOTING Interface</a>';
        }
    }
}
xarWebservicesMain();
?>
