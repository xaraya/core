<?php
/**
 * Curl Class
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */

/*
 * Simple curl class.
 *
 * @author  Jason Judge <judgej@xaraya.com>
 * @access  public
 * @param   $args['url'] The main URL for the curl session (optional)
 * @return  nothing
 * @throws  no exceptions
 * @todo    nice handling of protocols other than http.
 */

// Example:
// $curl = new xarCurl(); // or $curl = xarModAPIfunc('base', 'user', 'newcurl');
// $curl->seturl('http://www.xaraya.com/');
// $curl->get('module' => 'articles'); // could use post()
// $curl->get('aid' => '123');
// $page_text = $curl->exec();
// if ($curl->errno <> 0) {...raise error...}

class xarCurl
{
    // The curl object.
    // Extra methods and properties can be accessed through this property.
    var $curl;

    // The URL to go visit.
    var $url;

    // The GET and POST data.
    var $post = array();
    var $get = array();

    // Default method of sending data.
    var $sendmethod = 'POST';

    // Default GET and POST parameter separators.
    // TODO: these can be fetched from the PHP settings when the object is
    // initialised.
    var $get_start = '?';
    var $get_join = '&';
    var $post_join = '&';

    // Error code, in the event of failure.
    // No proprietory (i.e. Xaraya-specific) error handling here - the caller
    // can deal with any messages.
    // errno values:
    //  0:  success
    //  -1: class error (see $error for textual code, e.g. NO_SESSION, NO_URL)
    //  >0: curl error (see $error for message)
    var $errno = 0;
    var $error = '';
    var $http_code = 0;
    var $http_desc = '';

    // Result of a curl_getinfo() - cached so it is available even after the
    // session is closed.
    var $info = NULL;

    // Header information from the return message.
    var $header100 = array();
    var $header = array();

    // Curl info types: the information flags that getinfo() can accept.
    // The basic constants.
    // There are enough subtleties in the names that we can't
    // generalise them. Shame.
    var $info_types = array(
        CURLINFO_EFFECTIVE_URL => 'url',
        CURLINFO_HTTP_CODE => 'http_code',
        CURLINFO_HEADER_SIZE => 'header_size',
        CURLINFO_REQUEST_SIZE => 'request_size', 
        CURLINFO_FILETIME => 'filetime',
        CURLINFO_SSL_VERIFYRESULT => 'ssl_verify_result',
        CURLINFO_TOTAL_TIME => 'total_time',
        CURLINFO_NAMELOOKUP_TIME => 'namelookup_time',
        CURLINFO_CONNECT_TIME => 'connect_time',
        CURLINFO_PRETRANSFER_TIME => 'pretransfer_time',
        CURLINFO_SIZE_UPLOAD => 'size_upload',
        CURLINFO_SIZE_DOWNLOAD => 'size_download',
        CURLINFO_SPEED_DOWNLOAD => 'speed_download',
        CURLINFO_SPEED_UPLOAD => 'speed_upload',
        CURLINFO_CONTENT_LENGTH_DOWNLOAD => 'download_content_length',
        CURLINFO_CONTENT_LENGTH_UPLOAD => 'upload_content_length'
    );

    // The textual descriptions of known HTTP codes.
    // TODO: Some of these codes have related header records, such as
    // redirection URLs. We should collect those headers automatically
    // to make handling the exceptions easier.
    var $http_codes = array(
        // Success 2xx
        200 => 'OK',
        201 => 'CREATED',
        202 => 'Accepted',
        203 => 'Partial Information',
        204 => 'No Response',

        // Redirection 3xx
        301 => 'Moved',
        302 => 'Found',
        303 => 'Method',
        304 => 'Not Modified',

        // Error 4xx
        400 => 'Bad request',
        401 => 'Unauthorized',
        402 => 'PaymentRequired',
        403 => 'Forbidden',
        404 => 'Not found',
        
        // Error 5xx
        500 => 'Internal Error',
        501 => 'Not implemented',
        502 => 'Service temporarily overloaded',
        503 => 'Gateway timeout'
    );

    // Constructor: create the PHP curl object.
    // A url can be passed in at this point, or added later.
    // A session will be opened immediately the object is created.
    function xarCurl($args)
    {
        extract($args);

        if (!function_exists('curl_init')) {
            $this->errno = -1;
            $this->error = 'CURL_NOT_AVAILABLE';
            return false;
        }

        // Initialize a session.
        $this->init();
        
        // If the URL is not set here, then it can be set as a property later.
        // It is just included here for consistency with curl_init(string url).
        if (isset($url)) {
            $this->seturl($url);
        }

        // Later versions of curl have extra info types. Add these on now.
        if (defined('CURLINFO_CONTENT_TYPE')) {
            $this->info_types = array_merge(
                $this->info_types,
                array(
                    CURLINFO_CONTENT_TYPE => 'content_type',
                    CURLINFO_STARTTRANSFER_TIME => 'starttransfer_time',
                    CURLINFO_REDIRECT_TIME => 'redirect_time',
                    CURLINFO_REDIRECT_COUNT => 'redirect_count'
                )
            );
        }

        return true;
    }

    // Initialize a new session.
    // This only needs to be called to reopen a new session after the initial
    // session is closed. Alternatively, discard the object and create a new one.
    function init()
    {
        // Close any old session.
        $this->close();
        
        $this->curl = curl_init();

        // Set a few default options.
        $this->setopt(CURLOPT_HEADER, 1);
        $this->setopt(CURLOPT_RETURNTRANSFER, 1);

        // Reset other properties of this object.
        $this->url = NULL;
        $this->post = array();
        $this->get = array();
        $this->errno = 0;
        $this->error = '';
        $this->info = NULL;
        $this->header100 = array();
        $this->header = array();
    }

    // Set an option.
    // The session must be open to set an option.
    function setopt($option, $value)
    {
        if (!isset($this->curl)) {
            return false;
        }

        return curl_setopt($this->curl, $option, $value);
    }

    // Add GET or POST parameters (name/value pair or an array)
    function _param($name = '', $value = '', $type = '')
    {
        if (!isset($name) || $name == '') {
            return false;
        }

        if (is_array($name)) {
            $params = $name;
        }

        if (is_string($name)) {
            // TODO: multiple name/value pairs?
            $params = array($name => $value);
        }

        if (empty($type)) {
            $type = $this->sendmethod;
        }

        if ($type == 'POST') {
            $dest =& $this->post;
        } else {
            $dest =& $this->get;
        }

        foreach($params as $key => $val) {
            if (isset($val)) {
                $dest[] = urlencode($key) . '=' . urlencode($val);
            }
        }

        return true;
    }

    // Set the URL.
    function seturl($url)
    {
        // TODO: Do a quick check: we don't want XML-encoded
        // URLs here, just a plain URL.
        $this->url = $url;
    }
    
    // Add POST parameters (name/value pair or an array)
    // Can be called as many times as necessary to load up 
    // all the POST parameters.
    function post($name = '', $value = '')
    {
        return $this->_param($name, $value, 'POST');
    }

    // Add GET parameters (name/value pair or an array)
    // Same rules apply as for the post() method.
    function get($name = '', $value = '')
    {
        return $this->_param($name, $value, 'GET');
    }

    function uploadfile($filename)
    {
        // TODO: finish this off (not looked at this at all).
        // TODO: error if file cannot be read.
        $size = filesize($filename);
        $fp = fopen($filename, 'r');
        $this->setopt(CURLOPT_INFILE, $fp);
        $this->setopt(CURLOPT_UPLOAD, 1);
        $this->setopt(CURLOPT_INFILESIZE, $size);
    }

    // Execute the fetch.
    // TODO: handle a 'moved' response by going to the new location (calling exec a
    // second time will rebuild the GET and POST parameters on the new URL).
    function exec()
    {
        // Minimum requirements is for a curl object and a URL
        if (!isset($this->url)) {
            $this->errno = -1;
            $this->error = 'NO_URL';
            return false;
        }

        if (!isset($this->curl)) {
            $this->errno = -1;
            $this->error = 'NO_SESSION';
            return false;
        }

        // Handle POST parameters.
        if (!empty($this->post)) {
            $this->setopt(CURLOPT_POST, 1);
            $this->setopt(CURLOPT_POSTFIELDS, implode($this->post_join, $this->post));
        }

        // Handle GET parameters.
        if (!empty($this->get)) {
            // If the URL contains a '?' then assume it already has GET parameters.
            if (strpos($this->url, $this->get_start) > 0) {
                $joint = $this->get_join;
            } else {
                $joint = $this->get_start;
            }
            $this->url .= $joint . implode($this->get_join, $this->post);
        }

        $this->setopt(CURLOPT_URL, $this->url);
        $result = curl_exec($this->curl);

        // Store the error codes.
        $this->errno = curl_errno($this->curl);
        $this->error = curl_error($this->curl);

        // Store the info array.
        $this->getinfo();

        // Remove the 100 header.
        if (ereg('^HTTP/1.1 100', $result)) {
            $pos = strpos($result, "\r\n\r\n");
            if (!$pos) {
                $pos = strpos($result, "\n\n");
            }

            if ($pos) {
                // Put the header in a property for reference.
                $this->header100 = preg_split('/[\r\n]+/', substr($result, 0, $pos));
                $result = ltrim(substr($result, $pos));
            }
        }

        // Separate the payload from the HTTP headers.
        $pos = strpos($result, "\r\n\r\n");
        if (!$pos) {
            $pos = strpos($result, "\n\n");
            if (!$pos) {
                // No separation of content and headers.
                // Assume there is no data - just a header.
                $pos = strlen($result);
            }
        }

        // Split into header and data strings.
        $header = preg_split('/[\r\n]+/', trim(substr($result, 0, $pos)));
        $result = ltrim(substr($result, $pos));

        // Split each header line into a name/value pair.
        foreach ($header as $header_line) {
            $arr = split(':', $header_line, 2);
            if (count($arr) == 2) {
                // Put the header name/value pairs into a property array for reference.
                $this->header[trim($arr[0])] = trim($arr[1]);
            }
        }

        // Decode transfer-encoding
        if (isset($this->header['Transfer-Encoding']) && $this->header['Transfer-Encoding'] == 'chunked'){
            if (!$result = $this->_decode_chunked($result)){
                $this->errno = -1;
                $this->error = 'CHUNKED_DECODE_FAILED';
                return false;
            }
        }

        // Decode content-encoding.
        if (isset($this->header['Content-Encoding']) && $this->header['Content-Encoding'] != ''){
            if ($this->header['Content-Encoding'] == 'deflate' || $this->header['Content-Encoding'] == 'gzip') {
                // If decoding works, use it, otherwise assume data wasn't gzencoded.
                if (function_exists('gzinflate')) {
                    if ($this->header['Content-Encoding'] == 'deflate' && $degzdata = @gzinflate($result)) {
                        $result = $degzdata;
                    } elseif ($headers['Content-Encoding'] == 'gzip' && $degzdata = gzinflate(substr($result, 10))){
                        $result = $degzdata;
                    } else {
                        $this->errno = -1;
                        $this->error = 'DECODE_ERRORS';
                    }
                } else {
                    $this->errno = -1;
                    $this->error = 'ZLIB_REQUIRED';
                }
            }
        }

        // Return the data payload only.
        // The header data can be accessed as the 'header' property of this object.
        return $result;
    }

    // Get info fields from the curl object.
    // These info fields will remain available even after the curl session
    // has been closed.
    function getinfo($option = NULL)
    {
        // Info values and elements.
        // Some of these constants are only available on later
        // versions of curl/PHP.

        // Default return value.
        $result = false;

        if (isset($this->curl)) {
            // Get the info array fresh each time, so long as
            // the curl handle is open.
            $this->info = curl_getinfo($this->curl);
            $this->http_code = $this->info['http_code'];
            if (isset($this->http_descs[$this->http_code])) {
                $this->http_desc = $this->http_descs[$this->http_code];
            }
        }

        // Always return the info from the saved array, which will
        // either be fresh or a copy left from before the curl
        // handle was closed.
        if (isset($this->info)) {
            if (isset($option)) {
                if (isset($this->info_types[$option])) {
                    // We already have the option saved: return it.
                    $result = $this->info[$this->info_types[$option]];
                } else {
                    // Some new option that we don't know about yet: try to fetch it.
                    $result = @curl_getinfo($this->curl, $option);
                }
            } else {
                $result = $this->info;
            }
        }

        return $result;
    }

    // Close the curl handle (i.e. session), if currently open.
    function close()
    {
        if (!isset($this->curl)) {
            return false;
        }

        curl_close($this->curl);
        $this->curl = NULL;

        return true;
    }

    // Return the curl version.
    function version()
    {
        return curl_version();
    }

    /**
    * decode a string that is encoded w/ "chunked' transfer encoding
    * as defined in RFC2068 19.4.6
    *
    * This method extracted from other classes in Xaraya (see nusoap).
    *
    * @param    string $buffer
    * @returns  string
    * @access   public
    */
    function _decode_chunked($buffer)
    {
        $length = 0;
        $new = '';
        $crnl = "\r\n";
        
        // Read chunk-size, chunk-extension (if any) and CRLF.
        // Get the position of the linebreak.
        $chunkend = strpos($buffer, $crnl) + 2;
        $temp = substr($buffer, 0, $chunkend);
        $chunk_size = hexdec(trim($temp));
        $chunkstart = $chunkend;
        while ($chunk_size > 0) {
            $chunkend = strpos($buffer, $crnl, $chunkstart + $chunk_size);
            
            // Just in case we got a broken connection
            if ($chunkend == FALSE) {
                $chunk = substr($buffer, $chunkstart);
                // append chunk-data to entity-body
                $new .= $chunk;
                $length += strlen($chunk);
                break;
            }
            
            // read chunk-data and CRLF
            $chunk = substr($buffer, $chunkstart, $chunkend - $chunkstart);
            // append chunk-data to entity-body
            $new .= $chunk;
            $length += strlen($chunk);
            // read chunk-size and CRLF
            $chunkstart = $chunkend + 2;
            
            $chunkend = strpos($buffer, $crnl, $chunkstart) + 2;
            if ($chunkend == FALSE) {
                break; //Just in case we got a broken connection
            }
            $temp = substr($buffer, $chunkstart, $chunkend - $chunkstart);
            $chunk_size = hexdec(trim($temp));
            $chunkstart = $chunkend;
        }

        // This re-evaluation of the content length effectively hides the
        // encoding from the caller.
        $this->header['content-length'] = $length;
        unset($this->header['transfer-encoding']);

        return $new;
    }
}

?>
