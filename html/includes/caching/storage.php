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
    var $sizelimit = 10000000;
    var $reached = null;
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
        if (!empty($args['sizelimit'])) {
            $this->sizelimit = $args['sizelimit'];
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

    function getLastModTime()
    {
        return $this->modtime;
    }

    function isCached($key = '', $expire = 0, $log = 1)
    {
        return false;
    }

    function getCached($key = '', $output = 0, $expire = 0)
    {
        return '';
    }

    function setCached($key = '', $value = '', $expire = 0)
    {
    }

    function delCached($key = '')
    {
    }

    function flushCached($key = '')
    {
    }

    function cleanCached($expire = 0)
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

    function sizeLimitReached()
    {
        if (isset($this->reached)) {
            return $this->reached;
        }

        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if (file_exists($lockfile)) {
            $value = TRUE;
        } elseif (mt_rand(1,5) > 1) {
            // on average, 4 out of 5 pages go by without checking
            $value = FALSE;
        } else {
            $size = $this->getCacheSize();
            if ($size >= $this->sizelimit) {
                $value = TRUE;
                @touch($lockfile);
            } else {
                $value = FALSE;
            }
        }
        $this->reached = $value;

    // CHECKME: we don't need this cached variable anymore, do we ?
        if ($value && !xarCore_IsCached($this->type . '.Caching', 'cleaned')) {
            $this->cleanCached();
            xarCore_SetCached($this->type . '.Caching', 'cleaned', TRUE);
        }
        return $value;
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

    function getCachedList()
    {
        return array();
    }

    function getCachedKeys()
    {
        $list = $this->getCachedList();
        $keys = array();
        foreach ($list as $item) {
            if (empty($item['key'])) continue;
            $keys[$item['key']] = 1;
        }
        return array_keys($keys);
    }
}

?>
