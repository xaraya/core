<?php

class xarCache_Storage
{
    var $storage = ''; // fs, db, mc, ...
    var $cachedir = 'var/cache/output';
    var $type = ''; // page, block, template, ...
    var $code = ''; // URL factors et al.
    var $size = -1;
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

    function isCached($key = '', $code = '')
    {
        if (!empty($code)) {
            $this->code = $code;
        }
        return false;
    }

    function getCached($key = '', $code = '')
    {
        return '';
    }

    function setCached($key = '', $value = '', $code = '')
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
    }

    function sizeLimit()
    {
    }
}

?>
