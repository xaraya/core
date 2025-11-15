<?php

use Xaraya\Services\xar;

/**
 * Entrypoint for handling legacy & modern web services
 *
 * Loads the files required for a webservices request
 * @todo most of these types are no longer supported
 *
 * @package core\entrypoints
 * @subpackage entrypoints
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
*/
function xarWSLoader()
{
    /**
     * Load the bootstrap file for the minimal classes swe need
     */
    require_once __DIR__ . '/bootstrap.php';

    // initialize bootstrap
    sys::init();
    // start autoload
    sys::autoload();

    // add parent directory to include path - @deprecated 2.7.3 left-over from before?
    set_include_path(dirname(dirname(__FILE__)) . PATH_SEPARATOR . get_include_path());

    /**
     * Load the Xaraya core
     */
    xar::load();
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
    // Get Xaraya Services Class
    $xar = xar::getServicesClass();

    /*
     determine the server type, then
     create an instance of that server and
     serve the request according the the servers protocol
    */
    $xar->var()->get('type', $type, 'enum:rest:xmlrpc:trackback:soap:webdav:flashremoting:native', '');
    $xar->log()->message("In webservices with type=$type");
    $server = false;
    switch ($type) {
        /**
         * Entry point for XMLRPC web service
         */
        case 'xmlrpc' :
            // xmlrpc server does automatic processing directly
            if ($xar->mod()->isAvailable('xmlrpcserver')) {
                $server = $xar->mod()->apiFunc('xmlrpcserver', 'user', 'initxmlrpcserver');
            }
            if (!$server) {
                $xar->log()->message("Could not load XML-RPC server, giving up");
                // TODO: we need a specific handler for this
                echo $xar->mls()->translate('Could not load XML-RPC server');
            } else {
                $xar->log()->message("Created XMLRPC server");
            }
            break;
            /**
             * Entry point for JSONRPC web service
             */
        case 'jsonrpc' :
            // jsonrpc server does automatic processing directly
            if ($xar->mod()->isAvailable('jsonrpcserver')) {
                $server = $xar->mod()->apiFunc('jsonrpcserver', 'user', 'initjsonrpcserver');
            }
            if (!$server) {
                $xar->log()->message("Could not load JSON-RPC server, giving up");
                // TODO: we need a specific handler for this
                echo $xar->mls()->translate('Could not load JSON-RPC server');
            } else {
                $xar->log()->message("Created JSONRPC server");
            }
            break;
            /**
             * Entry point for trackback web service
             */
            // Hmmm, this seems a bit of a strange duck in this place here.
            // Trackback with its mixed spec. i.e. not an xml formatted request, but a simple POST
            // It doesnt mean however we can't treat the thing the same, ergo move the specifics out of here
        case 'trackback':
            if ($xar->mod()->isAvailable('trackback')) {
                $error = [];
                $xar->var()->get('url', $url, 'str:1:');
                if (empty($url)) {
                    // Gots to return the proper error reply
                    $error['errordata'] = $xar->mls()->translate('No URL Supplied');
                }
                // These are the specifics ;-)
                $xar->var()->find('title', $title, 'str:1', '');
                $xar->var()->find('blog_name', $blogname, 'str:1', '');
                $xar->var()->find('excerpt', $excerpt, 'str:1:255', '');
                if (empty($excerpt)) {
                    // Gots to return the proper error reply
                    $error['errordata'] = $xar->mls()->translate('Excerpt longer that 255 characters');
                }
                $xar->var()->get('id', $id, 'str:1:');
                if (empty($id)) {
                    // Gots to return the proper error reply
                    $error['errordata'] = $xar->mls()->translate('Bad TrackBack URL.');
                }

                $server = $xar->mod()->apiFunc(
                    'trackback',
                    'user',
                    'receive',
                    ['url'     =>  $url,
                        'title'   =>  $title,
                        'blogname' =>  $blogname,
                        'excerpt'  =>  $excerpt,
                        'id'      =>  $id,
                        'error'   =>  $error]
                );
            }
            if (!$server) {
                $xar->log()->message("Could not load trackback server, giving up");
                // TODO: we need a specific handler for this
                echo $xar->mls()->translate('Could not load trackback server');
            } else {
                $xar->log()->message("Created trackback server");
            }
            break;
            /**
             * Entry point for SOAP web service
             */
        case 'soap' :
            if (!extension_loaded('soap')) {
                echo $xar->mls()->translate('Could not load SOAP server');
                return;
            }
            if ($xar->mod()->isAvailable('soapserver')) {
                $server = $xar->mod()->apiFunc('soapserver', 'user', 'initsoapserver');

                if (!$server) {
                    // erm, where does this one come from? lucky because we did the api func?
                    $fault = new soap_fault('Server', '', 'Unable to start SOAP server', '');
                    // TODO: check this
                    echo $fault->serialize();
                }
                // Try to process the request
                if ($server) {
                    $server::handle();
                }
            }
            if (!$server) {
                $xar->log()->message("Could not load SOAP server, giving up");
                // TODO: we need a specific handler for this
                echo $xar->mls()->translate('Could not load SOAP server');
            } else {
                $xar->log()->message("Created SOAP server");
            }
            break;
            /**
             * Entry point for WebDAV web service
             */
        case 'webdav' :
            $xar->log()->message("WebDAV request");
            if ($xar->mod()->isAvailable('webdavserver')) {
                $server = $xar->mod()->apiFunc('webdavserver', 'user', 'initwebdavserver');
                if (!$server) {
                    $xar->log()->message('Could not load webdav server, giving up');
                    // TODO: we need a specific handler for this
                    throw new Exception('Could not load webdav server');
                } else {
                    $xar->log()->message("Created webdav server");
                }
                $server->ServeRequest();
            }
            if (!$server) {
                $xar->log()->message("Could not load webdav server, giving up");
                // TODO: we need a specific handler for this
                echo $xar->mls()->translate('Could not load webdav server');
            } else {
                $xar->log()->message("Created webdav server");
            }
            break;
            /**
             * Entry point for Flashremoting web service
             */
        case 'flashremoting' :
            $xar->log()->message("FlashRemoting request");
            if ($xar->mod()->isAvailable('flashservices')) {
                $server = $xar->mod()->apiFunc('flashservices', 'user', 'initflashservices');
                if (is_object($server)) {
                    $server->service();

                } else {
                    echo "could not create flashremoting server";

                }
            }
            if (!$server) {
                $xar->log()->message("Could not load flashremoting server, giving up");
                // TODO: we need a specific handler for this
                echo $xar->mls()->translate('Could not load flashremoting server');
            } else {
                $xar->log()->message("Created flashremoting server");
            }
            break;
            /**
             * Entry point for REST web service
             */
        case 'rest' :
            if ($xar->mod()->isAvailable('restserver')) {
                $server = $xar->mod()->apiFunc('restserver', 'user', 'initrestserver');
                if ($server) {
                    // Try to process the request
                    $server->ServeRequest();
                }
            }
            if (!$server) {
                $xar->log()->message("Could not load REST server, giving up");
                echo $xar->mls()->translate('Could not load REST server');
            } else {
                $xar->log()->message("Created REST server");
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
            $xar->var()->find('module', $module, 'str:1', 'base');
            $xar->var()->find('func', $func, 'str:1', 'default');
            try {
                $request = $xar->req()->getRequest($xar->req()->getURL());
                $data = $xar->mod()->apiFunc($module, 'ws', $func, $request->getFunctionArgs());
            } catch (Exception $e) {
                $data = $xar->mls()->translate('Unknown web service request');
            }
            echo $data;
            break;

            /**
             * Entry point for WSDL calls
             */
        default:
            if ($xar->req()->getServerVar('QUERY_STRING') == 'wsdl') {
                // FIXME: for now wsdl description is in soapserver module
                // consider making the webservices module a container for wsdl files (multiple?)
                $wsdllocation = $xar->ctl()->getBaseURL() . 'modules/soapserver/xaraya.wsdl';
                if (file_exists($wsdllocation)) {
                    $xar->log()->message("Moving to wsdl location");
                    header('Location: ' . $wsdllocation);
                } else {
                    $xar->log()->message("No wsdl location available, giving up");
                    // TODO: we need a specific handler for this
                    echo $xar->mls()->translate('Could not move to wsdl location. URL not found.');
                }
            } else {
                // TODO: show something nice(r) ?
                echo '<a href="ws.php/webhook">Webhook (path)</a><br />
<a href="ws.php?type=webhook">Webhook (query)</a><br />
<a href="ws.php?wsdl">WSDL</a><br />
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
 * Entrypoint for handling legacy web services
 */
function xarLegacyWebServices()
{
    /**
     * Set up for web services
     */
    xarWSLoader();
    /**
     * Process the web service request
     */
    xarWebservicesMain();
}

/**
 * Entrypoint for handling modern web services
 * @uses \sys::autoload()
 */
function xarModernWebServices(string $type)
{
    require_once __DIR__ . '/bootstrap.php';

    // initialize bootstrap
    sys::init();
    // start autoload
    sys::autoload();
    // initialize caching - delay until we need results
    //xar::cache()->init();
    // initialize database - delay until caching fails
    //xar::db()->init();
    // initialize modules
    //xar::mod()->init();
    // initialize users
    //xar::user()->init();

    // let whoever we call know this request comes from here ;-)
    $_SERVER['SERVER_FRAMEWORK'] = 'xaraya';

    switch ($type) {
        case 'webhook':
        case 'passthru':
            require_once dirname(__DIR__) . '/vendor/xaraya/webhooks/public/index.php';
            return;
        case 'htmx':
            // try out request context class
            //xar::req()->setRequestClass(\Xaraya\Context\RequestContext::class);
            // try out session context class
            //xar::session()->setSessionClass(\Xaraya\Context\SessionContext::class);
            //xar::load(xarCore::SYSTEM_USER);
            xar::load();
            //xar::ctl()->setBaseURL(xar::ctl()->getBaseURL());
            $htmx = new \Xaraya\Bridge\Routing\HtmxHandler('/htmx');
            //$request = xar::req()->getInstance();
            $request = null;
            $htmx->run($request);
            return;
        default:
            echo 'Unknown web service type';
            return;
    }
}

// list of "modern" web services relying on composer autoload
$modernTypes = ['webhook', 'passthru', 'htmx'];

// check path info first, then query type param
$type = '';
if (!empty($_SERVER['PATH_INFO'])) {
    // in case someone gets lost on the wrong path ;-)
    if (str_contains($_SERVER['PATH_INFO'], basename($_SERVER['SCRIPT_NAME']))) {
        header('Location: ' . $_SERVER['SCRIPT_NAME']);
        return;
    }
    // type is the first part in path info, e.g. /webhook/github/...
    $parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
    $type = reset($parts);
} elseif (!empty($_GET['type'])) {
    $type = $_GET['type'];
}

// let the right web service handle it
if (!empty($type) && in_array($type, $modernTypes)) {
    xarModernWebServices($type);
} else {
    xarLegacyWebServices();
}
