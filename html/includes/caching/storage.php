<?php

class xarCache_Storage
{
    var $storage = ''; // fs, db, mc, ...
    var $cachedir = 'var/cache/output';
    var $type = ''; // page, block, template, ...
    var $code = ''; // URL factors et al.
    var $size = null;
    var $compressed = false;

    /**
     * Constructor
     */
    function xarCache_Storage($args = array())
    {
        if (!empty($args['type'])) {
            $this->type = $args['type'];
        }
        if (!empty($args['cachedir'])) {
            $this->cachedir = $args['cachedir'];
        }
        if (!empty($args['code'])) {
            $this->code = $args['code'];
        }
        $this->cachedir = realpath($this->cachedir);
    }

    function setCode($code = '')
    {
        $this->code = $code;
    }

    function isCached($key = '')
    {
        return false;
    }

    function getCached($key = '')
    {
        return '';
    }

    function setCached($key = '', $value = '', $expire = null)
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
