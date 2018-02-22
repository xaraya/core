<?php
/**
 * @package core\caching
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

/**
 * Cache data using a dummy storage in memory (for the duration of one HTTP request)
 */

class xarCache_Dummy_Storage extends xarCache_Storage implements ixarCache_Storage
{
    public static $varcache = array();

    public function __construct(Array $args = array())
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
            $this->hits += 1;
            if ($log) $this->logStatus('HIT', $key);
            return true;
        } else {
            $this->misses += 1;
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
        $this->modtime = time();
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

    public function getCacheInfo()
    {
        $this->size = 0;
        $keylist = array_keys(self::$varcache[$this->prefix]);
        foreach ($keylist as $cache_key) {
            if (is_string(self::$varcache[$this->prefix][$cache_key])) {
                $this->size += strlen(self::$varcache[$this->prefix][$cache_key]);
            } else {
                //$this->size += 0;
            }
        }
        $this->items = count($keylist);
        return array('size'    => $this->size,
                     'items'   => $this->items,
                     'hits'    => $this->hits,
                     'misses'  => $this->misses,
                     'modtime' => $this->modtime);
    }

    public function getCachedList()
    {
        $list = array();
        // Note: this will probably be empty in most cases, unless you call this right after caching something
        $keylist = array_keys(self::$varcache[$this->prefix]);
        foreach ($keylist as $cache_key) {
        // CHECKME: this assumes the code is always hashed
            if (preg_match('/^(.*)-(\w*)$/',$cache_key,$matches)) {
                $key = $matches[1];
                $code = $matches[2];
            } else {
                $key = $cache_key;
                $code = '';
            }
            $time = time();
            if (is_string(self::$varcache[$this->prefix][$cache_key])) {
                $size = strlen(self::$varcache[$this->prefix][$cache_key]);
            } else {
                $size = '';
            }
            $check = '';
            $list[] = array('key'   => $key,
                            'code'  => $code,
                            'time'  => $time,
                            'size'  => $size,
                            'check' => $check);
        }
        return $list;
    }
}

?>