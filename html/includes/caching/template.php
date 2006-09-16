<?php
/**
 * Template caching abstraction
 *
 * @package blocklayout
 * @copyright The Digital Development Foundation, 2006
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @author Marcel van der Boom <mrb@hsdev.com>
 **/

 /**
  * Declare an interface for the xarTemplateCache class so we dont shoot
  * ourselves in the foot.
  *
  * @todo make caches all have the same interface
 **/
 interface IxarTemplateCache
 {
     static function init($dir, $active);
     static function getKey($fileName);
     static function saveKey($fileName);
     static function saveEntry($fileName, $data);
     static function isDirty($fileName);
     static function cacheFile($fileName);   // wrong for sure
     static function sourceFile($key);       // arguably wrong
 }
 
/**
 * Class to model the xar compiled template cache
 *
 * @package blocklayout
 * @todo bring this into the cache hierarchy in general so it can inherit from xarCache or something like that.
 * @todo this is still poorly abstracted, i would like to make a difference between the cache and its entries
 * @todo yes, i know this is similar to caching/storage/filesystem, but that one isnt ready yet :-) getting to that later.
 **/
class xarTemplateCache  extends Object implements ixarTemplateCache
{
    // Inactive means that we reuse one file in the cache all the time.
    private static $inactiveKeySeed    = 'youreallyreallyneedtocachetemplates';
    private static $dir         = '';    // location
    private static $active      = true;  // template cache is active by default.
    
    /**
     * Initialize template cache
     *
     * @param string $dir    location of the cache
     * @param bool   $active is the cache active?
    **/
    public static function init($dir, $active)
    { 
        if($active === false) self::$active = false;
        
        if(!is_writable($dir)) {
            $msg = "xarTemplateCache::init: Cannot write in the directory '#(1)', ";
            if(self::isActive()) {
                $msg .= "but the setting: 'cache templates' is set to 'On'.\n";
            } else {
                $msg .= "and the setting: 'cache template ' is set to 'Off.\n"
                      . "Although you can switch the template cache to off (not recommended), i still need that directory to be writable.\n";
            }
            $msg .= "You need to change the permissions on the mentioned file/directory.";
            throw new ConfigurationException($dir, $msg);
        }
        self::$dir = $dir;
    }
    
    /** 
     * Get the cache key for a sourcefile
     *
     * @access public
     * @param  string $fileName  For which file do we need the key?
     * @return string            The cache key for this sourcefilename
     * @todo what if cache is not active? still return the md5 key?
    **/
    public static function getKey($fileName) 
    {
        // Simple MD5 hash over the filename determines the key for the cache
        if(!self::isActive()) $fileName=self::$inactiveKeySeed;
        return md5($fileName); 
    }
    
    /**
     * Save the cache key for a sourcefile
     *
     * @access public
     * @param  string $sourceFileName  For which file are we entering the key?
     * @return bool true on success, false on failure
     * @todo   exceptions?
     * @todo   typically writing of these keys occurs in bursts, can we leave file open until we're done?
     * @todo   hmm, write the key when inactive too? feels like not, to keep it minimal
    **/
    public static function saveKey($fileName)
    {
        if(!self::isActive()) return true;
        if($fd = fopen(self::$dir . '/CACHEKEYS', 'a')) {
            fwrite($fd, self::getKey($fileName).': '.$fileName."\n");
            fclose($fd);
            return true;
        } 
        return false;
    }
    
    /**
     * Private methods
    **/
    private static function isActive()
    {
        return self::$active;
    }
    
    // Things really belonging somewhere else
    
    /**
     * Save an entry into the template cache
     *
     * @param  string $fileName  for which source file?
     * @param  string $data      what to save
     * @return bool   true on success, false on failure
     * @todo   doesnt belong here
    **/
    public static function saveEntry($fileName, $data)
    {
        // write data into the cache file
        if($fd = fopen(self::cacheFile($fileName), 'w')) {
            fwrite($fd, $data); fclose($fd);
        }
        // Add an entry into CACHEKEYS if needed
        return self::saveKey($fileName);
    }
    
    /**
     * Determine if a cache entry is dirty, i.e. needs recompilation.
     *
     * @param  string $fileName source file
     * @return bool  true when cache entry is dirty, false otherwise
    **/
    public static function isDirty($fileName)
    {
        if(!self::isActive()) return true; // always dirty
        
        $cacheFile = self::cacheFile($fileName);
        // Logic here is:
        // 1. if the compiled template file exists AND
        // 2. The source file does not exist ( we will have to fall back, but it's weird) OR
        // 3. modification time of source is smaller than modification time of the compiled template AND
        // 4. DEBUG: when the XSL transformation file has NOT been changed more recently than the compiled template
        // THEN we do NOT need to compile the file.
        if ( file_exists($cacheFile) &&
             ( !file_exists($fileName) ||
               ( filemtime($fileName) < filemtime($cacheFile)
                 && filemtime('includes/transforms/xar2php.xsl') < filemtime($cacheFile)
               ) ) ) return false; // not dirty
            
        return true; // either cache not active of entry needs recompilation
    }
    
    public static function cacheFile($fileName)
    {
        return self::$dir . '/' . self::getKey($fileName) . '.php';
    }
    
    public static function sourceFile($key)
    {
        $sourceFile = null;
        if(self::isActive()) {
            $fileName = $key . '.php';
            // Dont use try/catch here, as this may be called directly from
            // the exception handler (which we probably should avoid then?)
            // 
            if ($fd = @fopen(self::$dir . '/CACHEKEYS', 'r')) {
                while($cache_entry = fscanf($fd, "%s\t%s\n")) {
                    list($hash, $template) = $cache_entry;
                    
                    // Strip the colon
                    $hash = substr($hash,0,-1);
                    if($hash == $key) {
                        // Found the file, source is $template
                        $sourceFile = $template;
                        break;
                    }
                }
                fclose($fd);
            }
        }
        return $sourceFile;
    }
}
?>