<?php
/**
 * Check the status of some URL
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/**
 * Check the status of some URL
 *
 * @subpackage base
 * @author mikespub
 * @access public
 * @param $args['url'] string the absolute URL for the link
 * @param $args['method'] string the request method to use (default is HEAD, alternative is GET)
 * @param $args['skiplocal'] bool indicates if we want to skip checking local URLs (default is true)
 * @param $args['referer'] string optional referer (default is base URL of your site)
 * @param $args['follow'] bool indicates if we want to follow redirects or not (default is true)
 * @return integer status of the link
 */
function base_userapi_checklink($args)
{
    extract($args);

    if (!isset($url)) $url = '';
    if (!isset($method)) $method = 'HEAD';
    if (!isset($skiplocal)) $skiplocal = true;
    if (!isset($referer)) $referer = xarServerGetBaseURL();
    if (!isset($follow)) $follow = true;

    $invalid = false;
    $islocal = false;
    if (empty($url)) {
        $invalid = true;
    } elseif (strstr($url,'://')) {
        // only support http:// and ftp:// for now
    // TODO: support https:// later ?
        if (substr($url,0,7) != 'http://' && substr($url,0,6) != 'ftp://') {
            $invalid = true;
        }
        $server = xarServerGetHost();
        if (preg_match("!://($server|localhost|127\.0\.0\.1)(:\d+|)/!",$url)) {
            $islocal = true;
        }
    } elseif (substr($url,0,1) == '/') {
        $server = xarServerGetHost();
        $protocol = xarServerGetProtocol();
        $url = $protocol . '://' . $server . $url;
        $islocal = true;
    } else {
        $baseurl = xarServerGetBaseURL();
        $url = $baseurl . $url;
        $islocal = true;
    }
    if ($invalid) {
        return xarML('Invalid URL [#(1)]', $url);
    }

    if ($skiplocal && $islocal) {
        return 200; // assume OK
    }

    // see if we need to go through a proxy
    $proxyhost = xarModGetVar('base','proxyhost');
    if (!empty($proxyhost) && !$islocal) {
        $proxyport = xarModGetVar('base','proxyport');
        $fp = @fsockopen($proxyhost,$proxyport,$errno,$errstr,10);
        if (!$fp) {
            return xarML('Socket error #(1) : #(2) while retrieving URL #(3)', $errno, $errstr, $url);
        }
        // avoid unnecessary redirects
        $info = parse_url($url);
        if (empty($info['path'])) $url .= '/';
        $request = "$method $url HTTP/1.0\r\nHost: $proxyhost\r\nUser-Agent: Mozilla/5.0 (Xaraya - http://www.xaraya.com/)\r\nReferer: $referer\r\nConnection: close\r\n\r\n";

    } else {
        $info = parse_url($url);
        if (empty($info['host'])) $info['host'] = 'localhost';
        if (empty($info['port'])) $info['port'] = '80';
        if (empty($info['path'])) $info['path'] = '/';

        $fp = @fsockopen($info['host'],$info['port'],$errno,$errstr,10);
        if (!$fp) {
            return xarML('Socket error #(1) : #(2) while retrieving URL #(3)', $errno, $errstr, $url);
        }
        $uri = $info['path'];
        if (!empty($info['query'])) {
            $uri .= '?' . $info['query'];
        }
        $request = "$method $uri HTTP/1.0\r\nHost: $info[host]\r\nUser-Agent: Mozilla/5.0 (Xaraya - http://www.xaraya.com/)\r\nReferer: $referer\r\nConnection: close\r\n\r\n";
    }

    $size = fwrite($fp, $request);
    if (!$size) {
        return xarML('Error sending request for URL #(1)', $url);
    }
    $content = '';
    while (!feof($fp)) {
        $content .= fread($fp,4096);
    }
    fclose($fp);
    if (!preg_match('/^\s*HTTP\/[\d\.]+\s+(\d+)/s',$content,$matches)) {
        // some hosts (e.g. newsforge.com) don't even send HTTP headers or <html tags ?!
        if (!preg_match('/<html(\s+|>)/is',$content) &&
            !preg_match('/<body(\s+|>)/is',$content)) {
            $header = preg_replace('/\r\n\r\n.*$/s','',$content);
            return xarML('Invalid response headers for URL #(1) : #(2)', $url, $header);
        }
        // let's assume this is somewhat OK if there's some HTML in there
        $status = 203; // Non-Authoritative Information
    } else {
        $status = $matches[1];
    }
    switch ($status) {
        case 400: // Bad Request
        case 405: // Method Not Allowed
        case 501: // Not Implemented
            if ($method == 'HEAD') {
                // try again using GET method
                return xarModAPIFunc('base', 'user', 'checklink',
                                     array('url' => $url,
                                           'method' => 'GET',
                                           'skiplocal' => $skiplocal,
                                           'follow' => $follow));
            }
            break;
        case 505: // HTTP Version Not Supported
            // Duh - now what ? Pretend we're HTTP/1.1 ? Never saw this one in practice...
            break;
        case 301: // Moved Permanently
        case 302: // Found
            if ($follow && preg_match('/\nLocation:\s+(.+)\r?\n/',$content,$matches)) {
                $location = $matches[1];
            // TODO: handle relative redirects and endless loops (for messy servers)
                if ($location != $url && strstr($location,'://')) {
                    return xarModAPIFunc('base', 'user', 'checklink',
                                         array('url' => $location,
                                               'method' => $method,
                                               'skiplocal' => $skiplocal,
                                               'follow' => $follow));
                }
            }
            // otherwise fall through
        default:
            break;
    }
    return $status;
}

?>