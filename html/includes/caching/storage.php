<?php

class xarCache_Storage
{
    var $storage = ''; // filesystem, database, memcached, ...
    var $cachedir = 'var/cache/output';
    var $type = ''; // page, block, template, ...
    var $code = ''; // URL factors et al.
    var $size = null;
    var $compressed = false;
    var $expire = 0;

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

    function getCacheSize()
    {
        return $this->size;
    }

    function sizeLimit()
    {
    }
}

?>
