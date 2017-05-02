<?php
/**
 * Loads the files required for a webservices request
 *
 * @package core\entrypoints
 * @subpackage entrypoints
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
*/
 
function xarWSLoader()
{
/**
 * Load the layout file so we know where to find the Xaraya directories
 */
    $systemConfiguration = array();
    include 'var/layout.system.php';
    if (!isset($systemConfiguration['rootDir'])) $systemConfiguration['rootDir'] = '../';
    if (!isset($systemConfiguration['libDir'])) $systemConfiguration['libDir'] = 'lib/';
    if (!isset($systemConfiguration['webDir'])) $systemConfiguration['webDir'] = 'html/';
    if (!isset($systemConfiguration['codeDir'])) $systemConfiguration['codeDir'] = 'code/';
    $GLOBALS['systemConfiguration'] = $systemConfiguration;
    if (!empty($systemConfiguration['rootDir'])) {
        set_include_path($systemConfiguration['rootDir'] . PATH_SEPARATOR . get_include_path());
    }

/**
 * Load the bootstrap file for the minimal classes swe need
 */
    set_include_path(dirname(dirname(__FILE__)) . PATH_SEPARATOR . get_include_path());
    include 'bootstrap.php';

/**
 * Set up caching
 * Note: this happens first so we can serve cached pages to first-time visitors
 *       without loading the core
 */
    sys::import('xaraya.caching');
    xarCache::init();

/**
 * Load the Xaraya core
 */
    sys::import('xaraya.core');
    xarCore::xarInit(xarCore::SYSTEM_ALL);
}

/**
 * Xaraya WebServices Interface
 *
 * Entry point for webservices
 *
 * Just here to create a convenient url, the
 * actual work is done in the module, so we
 * are going as fast as we can to the module
 * to avoid redundancy.
 *
 * This script accepts one parameter: type [xmlrpc, soap]
 * with which the protocol is chosen
 *
 * Entry points for client:<br/>
 * XMLRPC        : http://host.com/ws.php?type=xmlrpc<br/>
 * JSONRPC       : http://host.com/ws.php?type=jsonrpc<br/>
 * SOAP          : http://host.com/ws.php?type=soap<br/>
 * TRACKBACK     : http://host.com/ws.php?type=trackback (Is this still right?)<br/>
 * WEBDAV        : http://host.com/ws.php?type=webdav<br/>
 * FLASHREMOTING : http://host.com/ws.php?type=flashremoting<br/>
 * REST          : http://host.com/ws.php?type=rest<br/>
 * NATIVE        : http://host.com/ws.php?type=native<br/>
 *
 * @package core\entrypoints
 * @subpackage entrypoints
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @access public
 * @author Miko
 */
function xarWebservicesMain()
{
/*
 determine the server type, then
 create an instance of that server and
 serve the request according the the servers protocol
*/
    xarVarFetch('type','enum:rest:xmlrpc:trackback:soap:webdav:flashremoting:native',$type,'');
    xarLogMessage("In webservices with type=$type");
    $server=false;
    switch($type) {
/**
 * Entry point for XMLRPC web service
 */
        case  'xmlrpc' :
            // xmlrpc server does automatic processing directly
            if (xarMod::isAvailable('xmlrpcserver')) {
                $server = xarMod::apiFunc('xmlrpcserver','user','initxmlrpcserver');
            }
            if (!$server) {
                xarLogMessage("Could not load XML-RPC server, giving up");
                // TODO: we need a specific handler for this
                echo xarML('Could not load XML-RPC server');
            } else {
                xarLogMessage("Created XMLRPC server");
            }
        break;
/**
 * Entry point for JSONRPC web service
 */
        case  'jsonrpc' :
            // jsonrpc server does automatic processing directly
            if (xarMod::isAvailable('jsonrpcserver')) {
                $server = xarMod::apiFunc('jsonrpcserver','user','initjsonrpcserver');
            }
            if (!$server) {
                xarLogMessage("Could not load JSON-RPC server, giving up");
                // TODO: we need a specific handler for this
                echo xarML('Could not load JSON-RPC server');
            } else {
                xarLogMessage("Created JSONRPC server");
            }
        break;
/**
 * Entry point for trackback web service
 */
        // Hmmm, this seems a bit of a strange duck in this place here.
        // Trackback with its mixed spec. i.e. not an xml formatted request, but a simple POST
        // It doesnt mean however we can't treat the thing the same, ergo move the specifics out of here
        case  'trackback':
            if (xarMod::isAvailable('trackback')) {
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

                $server = xarMod::apiFunc('trackback','user','receive',
                                        array('url'     =>  $url,
                                              'title'   =>  $title,
                                              'blogname'=>  $blogname,
                                              'excerpt'  =>  $excerpt,
                                              'id'      =>  $id,
                                              'error'   =>  $error));
            }
            if (!$server) {
                xarLogMessage("Could not load trackback server, giving up");
                // TODO: we need a specific handler for this
                echo xarML('Could not load trackback server');
            } else {
                xarLogMessage("Created trackback server");
            }
        break;
/**
 * Entry point for SOAP web service
 */
        case 'soap' :
            if (!extension_loaded('soap')) {
            }
            if(xarMod::isAvailable('soapserver')) {
                $server = xarMod::apiFunc('soapserver','user','initsoapserver');

                if (!$server) {
                    // erm, where does this one come from? lucky because we did the api func?
                    $fault = new soap_fault('Server','','Unable to start SOAP server', '');
                    // TODO: check this
                    echo $fault->serialize();
                }
                // Try to process the request
                if ($server) $server::handle();
            }
            if (!$server) {
                xarLogMessage("Could not load SOAP server, giving up");
                // TODO: we need a specific handler for this
                echo xarML('Could not load SOAP server');
            } else {
                xarLogMessage("Created SOAP server");
            }
        break;
/**
 * Entry point for WebDAV web service
 */
        case 'webdav' :
            xarLogMessage("WebDAV request");
            if(xarMod::isAvailable('webdavserver')) {
                $server = xarMod::apiFunc('webdavserver','user','initwebdavserver');
                if(!$server) {
                    xarLogMessage('Could not load webdav server, giving up');
                    // TODO: we need a specific handler for this
                    throw new Exception('Could not load webdav server');
                } else {
                    xarLogMessage("Created webdav server");
                }
                $server->ServeRequest();
            }
            if (!$server) {
                xarLogMessage("Could not load webdav server, giving up");
                // TODO: we need a specific handler for this
                echo xarML('Could not load webdav server');
            } else {
                xarLogMessage("Created webdav server");
            }
        break;
/**
 * Entry point for Flashremoting web service
 */
        case 'flashremoting' :
              xarLogMessage("FlashRemoting request");
            if(xarMod::isAvailable('flashservices')) {
              $server = xarMod::apiFunc('flashservices','user','initflashservices');
              if (is_object($server)) {
                  $server->service();

              } else {
                echo "could not create flashremoting server";

              }
            }
            if (!$server) {
                xarLogMessage("Could not load flashremoting server, giving up");
                // TODO: we need a specific handler for this
                echo xarML('Could not load flashremoting server');
            } else {
                xarLogMessage("Created flashremoting server");
            }
        break;
/**
 * Entry point for REST web service
 */
        case 'rest' :
            if(xarMod::isAvailable('restserver')) {
                $server = xarMod::apiFunc('restserver','user','initrestserver');
                if ($server) {
                    // Try to process the request
                    $server->ServeRequest();
                }
            }
            if (!$server) {
                xarLogMessage("Could not load REST server, giving up");
                echo xarML('Could not load REST server');
            } else {
                xarLogMessage("Created REST server");
            }
        break;
/**
 * Entry point for native web service
 *
 * This works like a "normal" Xaraya module call, but depends on wsapi functions (if they exist) in each module
 * The type is always "ws"
 * The module and function must be defined in the call
 * All other parameters passed in the call get bundled together in an array and passed to the called Xaraya function
 */
        case 'native' :
            xarVarFetch('module', 'str:1', $module, 'base',    XARVAR_NOT_REQUIRED);
            xarVarFetch('func',   'str:1', $func,   'default', XARVAR_NOT_REQUIRED);
            try {
                $request = xarController::getRequest(xarServer::getCurrentURL());
                $data = xarMod::apiFunc($module, 'ws', $func, $request->getFunctionArgs());
            } catch (Exception $e) {
                $data = xarML('Unknown web service request');
            }
            echo $data;
        break;
        
/**
 * Entry point for WSDL calls
 */
        default:
            if (xarServer::getVar('QUERY_STRING') == 'wsdl') {
                // FIXME: for now wsdl description is in soapserver module
                // consider making the webservices module a container for wsdl files (multiple?)
                $wsdllocation = xarServer::getBaseURL() . 'modules/soapserver/xaraya.wsdl';
                if (file_exists($wsdllocation)) {
                    xarLogMessage("Moving to wsdl location");
                    header('Location: ' . $$wsdllocation);
                } else {
                    xarLogMessage("No wsdl location available, giving up");
                    // TODO: we need a specific handler for this
                    echo xarML('Could not move to wsdl location. URL not found.');
                }
            } else {
                // TODO: show something nice(r) ?
                echo '<a href="ws.php?wsdl">WSDL</a><br />
<a href="ws.php?type=xmlrpc">XML-RPC Interface</a><br />
<a href="ws.php?type=trackback">Trackback Interface</a><br />
<a href="ws.php?type=soap">SOAP Interface</a><br/>
<a href="ws.php?type=webdav">WebDAV Interface</a><br/>
<a href="ws.php?type=flashremoting">FLASHREMOTING Interface</a><br/>
<a href="ws.php?type=native">Native Xaraya Interface</a>';
        }
    }
}

/**
 * Set up for web services
 */
xarWSLoader();
/**
 * Process the web service request
 */
xarWebservicesMain();

?>