<?php
/**
 * Get a file from the Internet
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Gets a file from the Internet
 *
 * Returns the content of the file (possibly cached). Don't use this to download huge files :)
 * @author mikespub
 * @access public
 * @param $args['url'] string the absolute URL for the file
 * @param $args['cached'] bool indicates whether this file can be cached or not
 * @param $args['cachedir'] string directory (under var/) where the file should be cached
 * @param $args['refresh'] integer refresh time for the file in seconds
 * @param $args['extension'] string file extension to use after the MD5-hashed filename in cache
 * @param $args['archive'] bool indicates if we want to re-create a directory structure and archive the file as is
 * @param $args['superrors'] bool indicates whether we want to die without an error shown (for blocks)
 * @return string content of the file
 */
function base_userapi_getfile($args)
{
    extract($args);

    if (!isset($url)) $url = '';

    // default not cached
    if (!isset($cached)) $cached = false;

    // default 'cache' dir under var/
    if (!isset($cachedir)) $cachedir = 'cache';

    // default refresh after an hour
    if (!isset($refresh)) $refresh = 3600;

    // default extension is .php
    if (!isset($extension)) $extension = '.php';

    // default no archive
    if (!isset($archive)) $archive = false;

    // default don't supress the errors
    if (!isset($superrors)) $superrors = false;

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
        if (!$superrors){
            $msg = xarML('Invalid URL [#(1)]', $url);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException($msg));
        }
        return;
    }

    // check if this file is already cached
    if ($cached) {
        $vardir = xarCoreGetVarDirPath();
        if (!$archive) {
            $file = $vardir . '/' . $cachedir . '/' . md5($url) . $extension;
        } else {
            $info = parse_url($url);
            if (!empty($cachedir)) {
                $path = $vardir . '/' . $cachedir;
            } else {
                $path = $vardir;
            }
            if (!$islocal) {
                $path .= '/' . xarVarPrepForOS($info['host']);
            }
            if (!is_dir($path)) {
                mkdir($path);
            }
            $fileparts = split('/',$info['path']);
            if (count($fileparts) > 0) {
                array_shift($fileparts);
            }
            if (count($fileparts) > 0) {
                $filename = array_pop($fileparts);
            }
            if (count($fileparts) > 0) {
                foreach ($fileparts as $part) {
                    if ($part === '') continue;
                    $path .= '/' . xarVarPrepForOS($part);
                    if (!is_dir($path)) {
                        mkdir($path);
                    }
                }
            }
            if (empty($filename)) {
                $filename = 'index' . $extension;
            }
            $file = $path . '/' . $filename;
            if (!empty($info['query'])) {
                $file .= '_' . xarVarPrepForOS($info['query']);
            }
        }
        $expire = time() - $refresh;
        if (file_exists($file) && filemtime($file) > $expire) {
            $fp = @fopen($file, 'rb');
            if (!$fp) {
                if (!$superrors){
                    $msg = xarML('Error opening cache file #(1) for URL #(2)', $file, $url);
                    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                }
                return;
            }
            $content = '';
            while (!feof($fp)) {
                $content .= fread($fp, filesize($file));
            }
            fclose($fp);
            return $content;
        }
    }

    // see if we need to go through a proxy
    $proxyhost = xarModGetVar('base','proxyhost');
    if (!empty($proxyhost) && !$islocal) {
        $proxyport = xarModGetVar('base','proxyport');
        $fp = @fsockopen($proxyhost,$proxyport,$errno,$errstr,10);
        if (!$fp) {
            if (!$superrors){
                $msg = xarML('Socket error #(1) : #(2) while retrieving URL #(3)', $errno, $errstr, $url);
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
            }
            return;
        }
        $baseurl = xarServerGetBaseURL();
        $request = "GET $url HTTP/1.0\r\nHost: $proxyhost\r\nUser-Agent: Xaraya (http://www.xaraya.com/)\r\nReferer: $baseurl\r\nConnection: close\r\n\r\n";
        $size = fwrite($fp, $request);
        if (!$size) {
            if (!$superrors){
                $msg = xarML('Error sending request for URL #(1)', $url);
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
            }
            return;
        }
        $content = '';
        while (!feof($fp)) {
            $content .= fread($fp,4096);
        }
        fclose($fp);
        if (!preg_match('/^\s*HTTP\/[\d\.]+\s+(\d+)/s',$content,$matches)) {
            $header = preg_replace('/\r\n\r\n.*$/s','',$content);
            if (!$superrors){
                $msg = xarML('Invalid response headers for URL #(1) : #(2)', $url, $header);
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
            }
            return;
        }
        $status = $matches[1];
        switch ($status) {
            case 200: // OK
                break;
            case 301: // Moved Permanently
            case 302: // Found
                if (preg_match('/\nLocation:\s+(.+)\r?\n/',$content,$matches)) {
                    $location = $matches[1];
                // TODO: handle relative redirects and endless loops (for messy servers)
                    if ($location != $url && strstr($location,'://')) {
                        return xarModAPIFunc('base', 'user', 'getfile',
                                             array('url' => $location,
                                                   'cached' => $cached,
                                                   'cachedir' => $cachedir,
                                                   'refresh' => $refresh,
                                                   'extension' => $extension,
                                                   'archive' => $archive));
                    }
                }
                // otherwise fall through
            case 206: // Partial Content - shouldn't be allowed for HTTP/1.0
            default:
                $header = preg_replace('/\r\n\r\n.*$/s','',$content);
                if (!$superrors){
                    $msg = xarML('Invalid status #(1) for URL #(2) : #(3)', $status, $url, $header);
                    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                }
                return;
                break;
        }
        // remove HTTP headers
        $content = preg_replace('/^.*?\r\n\r\n/s','',$content);

    } else {
    // TODO: we probably want some fancier error checking here too :-)
        if (!ini_get('allow_url_fopen')) {
            if (!$superrors){
                $msg = xarML('PHP is not currently configured to allow URL retrieval
                             of remote files.  Please turn on #(1) to use the base 
                             module getfile userapi.', '\'allow_url_fopen\'');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
            }
            return;
        }
        $lines = @file($url);
        if (empty($lines)) {
            if (!$superrors){
                $msg = xarML('Invalid URL #(1)', $url);
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
            }
            return;
        }
        $content = implode('',$lines);
    }

    if ($cached && is_dir($vardir . '/' . $cachedir)) {
        $fp = @fopen($file,'wb');
        if (!$fp) {
            if (!$superrors){
            $msg = xarML('Error saving URL #(1) to cache file #(2)', $url, $file);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException($msg));
            }
            return;
        }
        $size = fwrite($fp, $content);
        if (!$size || $size < strlen($content)) {
            if (!$superrors){
                $msg = xarML('URL #(1) truncated to #(2) bytes when saving to cache file #(3)', $url, $size, $file);
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
            }
            return;
        }
        fclose($fp);
    }

    return $content;
}

?>