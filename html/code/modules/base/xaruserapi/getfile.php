<?php
/**
 * Get a file from the Internet
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * 
 * Gets a file from the Internet
 *
 * Returns the content of the file (possibly cached). Not intended for large files.

 * @author mikespub
 * @param array    $args Array of optional parameters<br/>
 *        string   $args['url'] The absolute URL for the file<br/>
 *        boolean  $args['cached'] Indicates whether this file can be cached or not<br/>
 *        string   $args['cachedir'] Directory (under var/) where the file should be cached<br/>
 *        integer  $args['refresh'] Integer refresh time for the file in seconds<br/>
 *        string   $args['extension'] File extension to use after the MD5-hashed filename in cache<br/>
 *        boolean  $args['archive'] Indicates if we want to re-create a directory structure and archive the file as is<br/>
 *        boolean  $args['superrors'] Indicates whether we want to die without an error shown (for blocks)
 * @return string Content of the file
 */
function base_userapi_getfile(Array $args=array())
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
        $server = xarServer::getHost();
        if (preg_match("!://($server|localhost|127\.0\.0\.1)(:\d+|)/!",$url)) {
            $islocal = true;
        }
    } elseif (substr($url,0,1) == '/') {
        $server = xarServer::getHost();
        $protocol = xarServer::getProtocol();
        $url = $protocol . '://' . $server . $url;
        $islocal = true;
    } else {
        $baseurl = xarServer::getBaseURL();
        $url = $baseurl . $url;
        $islocal = true;
    }
    if ($invalid) {
        if (!$superrors) throw new BadParameterException($url);
    }

    // check if this file is already cached
    if ($cached) {
        $vardir = sys::varpath();
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
            $fileparts = explode('/',$info['path']);
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
                if (!$superrors) throw new BadParameterException(array($file,$url),'Error opening cache file #(1) for URL #(2)');
            }
            $content = '';
            if (filesize($file)) {
                while (!feof($fp)) {
                    $content .= fread($fp, filesize($file));
                }
            }
            fclose($fp);
            return $content;
        }
    }

    // see if we need to go through a proxy
    $proxyhost = xarModVars::get('base','proxyhost');
    if (!empty($proxyhost) && !$islocal) {
        $proxyport = xarModVars::get('base','proxyport');
        $fp = @fsockopen($proxyhost,$proxyport,$errno,$errstr,10);
        if (!$fp) {
            if (!$superrors)
                throw new BadParameterException(array($errno,$errstr,$url),'Socket error #(1) : #(2) while retrieving URL #(3)');
        }
        $baseurl = xarServer::getBaseURL();
        $request = "GET $url HTTP/1.0\r\nHost: $proxyhost\r\nUser-Agent: Xaraya (http://www.xaraya.com/)\r\nReferer: $baseurl\r\nConnection: close\r\n\r\n";
        $size = fwrite($fp, $request);
        if (!$size) {
            if (!$superrors)
                throw new BadParameterException($url,'Error sending request for URL #(1)');
        }
        $content = '';
        while (!feof($fp)) {
            $content .= fread($fp,4096);
        }
        fclose($fp);
        if (!preg_match('/^\s*HTTP\/[\d\.]+\s+(\d+)/s',$content,$matches)) {
            $header = preg_replace('/\r\n\r\n.*$/s','',$content);
            if (!$superrors)
                throw new BadParameterExceptions(array($url,$header),'Invalid response headers for URL #(1) : #(2)');
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
                        return xarMod::apiFunc('base', 'user', 'getfile',
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
                if (!$superrors)
                    throw new BadParameterException(array($status, $url, $header),'Invalid status #(1) for URL #(2) : #(3)');
                break;
        }
        // remove HTTP headers
        $content = preg_replace('/^.*?\r\n\r\n/s','',$content);

    } else {
        // TODO: we probably want some fancier error checking here too :-)
        // use curl instead of fopen method if we can (not supported by all hosts these days)
        sys::import('modules.base.class.xarCurl');
        $curl = new xarCurl(array('url' => $url));
        // check that curl initialised ok
        if ($curl->errno <> 0) {
          // check for allow fopen if curl returned an error
          if (!xarFuncIsDisabled('ini_set')) ini_set('allow_url_fopen', 1);
          if (!ini_get('allow_url_fopen')) {
             if (!$superrors)
                throw new ConfigurationException('allow_url_fopen','PHP is not currently configured to allow URL retrieval
                             of remote files.  Please turn on #(1) to use the base module getfile userapi.');
          }
          $lines = @file($url);
        // use cURL instead
        } else {
          $curl->seturl($url);
          $lines = $curl->exec();
          // make sure we got a valid file
          if ($curl->errno <> 0) {
            // CHECKME: do we want to raise an exception here? or leave for BadParameter to catch later?
            /*
             if (!$superrors)
                throw new BadParameterException(array($curl->error, $url),'cURL could not retrieve the file at #(2). Failed with error #(1)');
            */
            $lines = '';
          }
        }
        if (empty($lines)) {
            if (!$superrors) throw new BadParameterException($url);
        }
        if (is_array($lines)) $content = implode('',$lines);
        else $content = $lines;
    }

    if ($cached && is_dir($vardir . '/' . $cachedir)) {
        $fp = @fopen($file,'wb');
        if (!$fp) {
            if (!$superrors)
                throw new BadParameterException(array($url,$file),'Error saving URL #(1) to cache file #(2)');
        }
        $size = fwrite($fp, $content);
        if (!$size || $size < strlen($content)) {
            if (!$superrors)
                throw new BadParameterException(array($url,$size,$file),'URL #(1) truncated to #(2) bytes when saving to cache file #(3)');
        }
        fclose($fp);
    }

    return $content;
}
?>