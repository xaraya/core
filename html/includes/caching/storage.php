<?php

class xarCache_Storage
{
    var $storage = ''; // filesystem, database, memcached, ...
    var $cachedir = 'var/cache/output';
    var $type = ''; // page, block, template, ...
    var $code = ''; // URL factors et al.
    var $size = null;
    var $numitems = 0;
    var $compressed = false;
    var $expire = 0;
    var $logfile = null;
    var $logsize = 2000000; // for each logfile
    var $modtime = 0; // last modification time

    /**
     * Constructor
     */
    function xarCache_Storage($args = array())
    {
        if (!empty($args['type'])) {
            $this->type = strtolower($args['type']);
        }
        if (!empty($args['cachedir'])) {
            $this->cachedir = $args['cachedir'];
        }
        if (!empty($args['code'])) {
            $this->code = $args['code'];
        }
        if (!empty($args['expire'])) {
            $this->expire = $args['expire'];
        }
        if (!empty($args['logfile'])) {
            $this->logfile = realpath($args['logfile']);
        }
        if (!empty($args['logsize'])) {
            $this->logsize = $args['logsize'];
        }
        $this->cachedir = realpath($this->cachedir);
    }

    function setCode($code = '')
    {
        $this->code = $code;
    }

    function setExpire($expire = 0)
    {
        $this->expire = $expire;
    }

    function getModTime()
    {
        return $this->modtime;
    }

    function isCached($key = '')
    {
        return false;
    }

    function getCached($key = '')
    {
        return '';
    }

    function setCached($key = '', $value = '')
    {
    }

    function delCached($key = '')
    {
    }

    function flushCached($key = '')
    {
    }

    function cleanCached()
    {
    }

    function getCacheSize($countitems = false)
    {
        return $this->size;
    }

    function getCacheItems()
    {
        return $this->numitems;
    }

    function sizeLimit()
    {
    }

    function logStatus($status = 'MISS', $key = '')
    {
        if (empty($this->logfile) || empty($_SERVER['HTTP_HOST']) ||
            empty($_SERVER['REQUEST_URI']) || empty($_SERVER['REMOTE_ADDR'])) {
            return;
        }

        $time = time();
        $addr = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '-';
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        //$ref = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-';
        $type = $this->type;
        $code = $this->code;

        if (file_exists($this->logfile) && filesize($this->logfile) > $this->logsize) {
            $fp = @fopen($this->logfile, 'w');
        } else {
            $fp = @fopen($this->logfile, 'a');
        }
        if ($fp) {
            @fwrite($fp, "$time $status $type $key $code $addr $url\n");
            @fclose($fp);
        }
    }

    function saveFile($key = '', $filename = '')
    {
    }
}

?>
