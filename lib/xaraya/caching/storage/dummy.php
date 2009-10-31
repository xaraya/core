<?php
/**
 * Cache data using a dummy storage in memory (for the duration of one HTTP request)
 */
class xarCache_Dummy_Storage extends xarCache_Storage
{
    public static $varcache = array();

    public function __construct(array $args = array())
    {
        parent::__construct($args);
        // use the prefix as array key in self::$varcache here
        if (!isset(self::$varcache[$this->prefix])) {
            self::$varcache[$this->prefix] = array();
        }
        $this->storage = 'dummy';
    }

    public function setNamespace($namespace = '')
    {
        parent::setNamespace($namespace);
        // use the prefix as array key in self::$varcache here
        if (!isset(self::$varcache[$this->prefix])) {
            self::$varcache[$this->prefix] = array();
        }
    }

    public function getCacheKey($key = '')
    {
        // use the prefix as array key in self::$varcache here
        // add the code suffix
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        return $key;
    }

    public function isCached($key = '', $expire = 0, $log = 1)
    {
        $cache_key = $this->getCacheKey($key);
        if (isset(self::$varcache[$this->prefix]) && isset(self::$varcache[$this->prefix][$cache_key])) {
            // FIXME: dummy doesn't keep track of modification times !
            //$this->modtime = 0;
            if ($log) $this->logStatus('HIT', $key);
            return true;
        } else {
            if ($log) $this->logStatus('MISS', $key);
            return false;
        }
    }

    public function getCached($key = '', $output = 0, $expire = 0)
    {
        $cache_key = $this->getCacheKey($key);
        $value = self::$varcache[$this->prefix][$cache_key];
        if ($output) {
            // output the value directly to the browser
            echo $value;
            return true;
        } else {
            return $value;
        }
    }

    public function setCached($key = '', $value = '', $expire = 0)
    {
        $cache_key = $this->getCacheKey($key);
        self::$varcache[$this->prefix][$cache_key] = $value;
    }

    public function delCached($key = '')
    {
        $cache_key = $this->getCacheKey($key);
        unset(self::$varcache[$this->prefix][$cache_key]);
    }

    public function flushCached($key = '')
    {
        if (empty($key)) {
            unset(self::$varcache[$this->prefix]);
        } else {
            // clean up partial keys ?
        }
    }

    public function sizeLimitReached()
    {
        return false;
    }

    public function getCachedKeys()
    {
        return array_keys(self::$varcache[$this->prefix]);
    }
}

?>
