<?php
/**
 * Xaraya CSS class library
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/70.html
**/
/**
 * Base CSS class
**/
class xarCSS extends Object
{
/**
 * Defines for this library
 *
 * @author Andy Varganov <andyv@xaraya.com>
 * @author Chris Powis   <crisp@xaraya.com>
 * @todo evaluate if these are really necessary
**/
    // the name of the module and the modvar to use for storing this object
    const STORAGE_MODULE           = 'themes';
    const STORAGE_VARIABLE         = 'css.libs';
    // base folder to look in for libs
    const LIB_BASE                 = 'style';
    const LIB_BASE_ALT             = 'xarstyles';

    const CSSRELSTYLESHEET         = "stylesheet";
    const CSSRELALTSTYLESHEET      = "alternate";
    const CSSTYPETEXT              = "text/css";
    const CSSMEDIA                 = "media";
    const CSSMEDIATV               = "tv";
    const CSSMEDIATTY              = "tty";
    const CSSMEDIAALL              = "all";
    const CSSMEDIAPRINT            = "print";
    const CSSMEDIAAURAL            = "aural";
    const CSSMEDIASCREEN           = "screen";
    const CSSMEDIABRAILLE          = "braille";
    const CSSMEDIAHANDHELD         = "handheld";
    const CSSMEDIAPROJECTION       = "projection";
    const CSSCOMMONBASE            = "style";
    const CSSCOMMONFILE            = "style";
    const CSSCOMMONFILEEXT         = "css";
    //const CSSCOMMONCORE            = "xarcore-xhtml1-strict";
    const CSSCOMMONCORE            = "core";

    private static $instance;
    private static $css;

    // array of sheet objects
    public $local_libs      = array();
    // array of sheet objects
    public $remote_libs     = array();
    // default sheets to load...
    public $default_libs    = array();

    // experimental combine/compress options
    private $cacheDir   = 'cache/css';
    private $combined   = true;
    private $compressed = true;

/**
 * object constructor
 *
 * Unless the modvar is deleted outside this object
 * this function will only ever been run once (first run)
 * so we use it to populate the initial defaults
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access private prevents direct creation of this singleton, use getInstance()
 * @params none
 * @throws none
 * @return void
**/

    private function __construct()
    {
        $this->combined   = xarModVars::get('themes', 'css.combined');
        $this->compressed = xarModVars::get('themes', 'css.compressed');
    }

/**
 * Object wakeup
 *
 * This is called immediately after the object is unserialized
 * this function is only ever run once per page request
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params none
 * @throws none
 * @returns void
**/
    public function __wakeup()
    {
        // Check what libraries are present in the filesystem
        $this->refresh();
        // Load the default libraries
        foreach($this->default_libs as $lib) {
//            $this->register($lib);
        }
    }
/**
 * Object sleep method
 *
 * This is called whenever the object is serialized
 * this function is only ever run once per page request
 * Use it to perform operations immediately before the object goes out of scope
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params none
 * @throws none
 * @returns array public object properties to store values for
**/
    public function __sleep()
    {
        // set the last run time before we exit
        $this->last_run = time();
        // return the array of public property names to store
        return array_keys($this->getPublicProperties());
    }

/**
 * Object destructor
 *
 * This method is called when the object goes out of scope,
 * typically this will be when xaraya exits
 * but can be forced at any time by unsetting this object
 *
 * At this point we want to store the current object, serialized
 * To the modvar specified by the module and modvar constants
**/
    public function __destruct()
    {
        // basically, we serialize and set this object as a modvar
        // xarModVars::set can be a little flaky,
        // this workaround seems to do the trick
        // NOTE: when we call serialize here, the __sleep() magic method is called
        try {
            xarModVars::set(xarCSS::STORAGE_MODULE, xarCSS::STORAGE_VARIABLE, serialize($this));
        } catch (Exception $e) {
            xarModVars::delete(xarCSS::STORAGE_MODULE, xarCSS::STORAGE_VARIABLE);
            xarModVars::set(xarCSS::STORAGE_MODULE, xarCSS::STORAGE_VARIABLE, serialize($this));
       }
    }

/**
 * Get instance function
 *
 * This is the only way to obtain this object instance
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params none
 * @return Object current instance
 * @throws none
 *
**/
    public static function getInstance_old()
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            // try unserializing the stored modvar
            self::$instance = @unserialize(xarModVars::get(xarCSS::STORAGE_MODULE, xarCSS::STORAGE_VARIABLE));
            // fall back to new instance (first run)
            if (empty(self::$instance)) {
                $c = __CLASS__;
                // this is the one and only time the __construct() method will be run
                self::$instance = new $c;
            }
        }
        self::$instance->combined   = xarModVars::get('themes', 'css.combined');
        self::$instance->compressed = xarModVars::get('themes', 'css.compressed');
        return self::$instance;
    }

/**
 * Refresh function
 *
 * 1. Identify all local javascript libraries
 * 2. For each library create the corresponding object
 * 3. Find all the associated files and add them to the object
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @author Marc Lutolf <mfl@netspan.ch>
 * @access public
 * @params none
 * @return none
 * @throws none
 *
**/
    public function refresh()
    {
        // now find all libs in the filesystem
        // we want to look in all active themes
        $filter = array('Class' => 2, 'State' => XARTHEME_STATE_ACTIVE);
        $themes = xarMod::apiFunc('themes', 'admin', 'getlist', $filter);
        // we want to look in all active modules
        $modules = xarMod::apiFunc('modules', 'admin', 'getlist',
            array('filter' => array('State' => XARMOD_STATE_ACTIVE)));
        // we want to look in all active themes
        // we want to look in all active modules
        // set default paths and filenames
        $baseDir     = xarTpl::getBaseDir();
        $themeDir    = xarTpl::getThemeDir();
        $themeName   = xarTpl::getThemeName();
        $commonDir   = xarTpl::getThemeDir('common');
        $codeDir     = sys::code();
        $libBase     = xarCSS::LIB_BASE;
        $libBaseAlt  = xarCSS::LIB_BASE_ALT;

        $paths = array();
        // search common too
        $themes[] = array('osdirectory' => 'common');
        // first we want to look in each active theme...
        foreach ($themes as $theme) {
            $themeOSDir = $theme['osdirectory'];
            // look for libs in themes/<theme>/style/*
            $paths[] = "{$baseDir}/{$themeOSDir}/{$libBase}";
            $paths[] = "{$baseDir}/{$themeOSDir}/{$libBaseAlt}";       // Remove?
            // then in each active module, this theme
            foreach ($modules as $mod) {
                $modOSDir = $mod['osdirectory'];
                // look in themes/<theme>/modules/<module>/lib/*
                $paths[] = "{$baseDir}/{$themeOSDir}/modules/{$modOSDir}/{$libBase}";       // Remove?
                $paths[] = "{$baseDir}/{$themeOSDir}/modules/{$modOSDir}/{$libBaseAlt}";
            }
        }
        // now we look in each active module
        foreach ($modules as $mod) {
            $modOSDir = $mod['osdirectory'];
            // look in code/modules/<module>/xartemplates/lib/libname/*
            $paths[] = "{$codeDir}modules/{$modOSDir}/{$libBaseAlt}";
        }

        // build an array of potential libraries
        // Below the lib directory we expect to find a directory with a library's name
        // Below that the next level must be one or more directories with different versions of the library
        sys::import('xaraya.version');
        $libs = array();
        foreach ($paths as $path) {
            if (!is_dir($path)) continue;
            $folders = $this->getFolders($path, 1);
            if (empty($folders)) continue;
            foreach (array_keys($folders) as $lib) {
                $subpath = $path . "/" . $lib;
                $versions = $this->getFolders($subpath, 1);
                if (empty($versions)) continue;                
                
                // Remove any versions which are not valid
                foreach ($versions as $key => $value) {
                    $valid = xarVersion::parse($key);
                    if(!$valid) unset($versions[$key]);
                }
                
                // keep track of found libs
                $libs[$lib] = 1;
                // init lib if necessary
                if (!isset($this->local_libs[$lib]))
                    $this->local_libs[$lib] = new xarCSSLib($lib);
                    
                // refresh lib
                $this->local_libs[$lib]->versions = $versions;
                $this->local_libs[$lib]->findFiles();
                // Sort by version in descending order
                krsort($this->local_libs[$lib]->styles);
            }
        }
        // remove any missing libs
        foreach ($this->local_libs as $compare => $curlib) {
            if (!isset($libs[$compare]))
                unset($this->local_libs[$compare]);
        }

    }

    public static function getFolders($path, $levels=0)
    {
        $folders = array();
        try {
            foreach (new DirectoryIterator($path) as $item) {
                if ($item->isDir() && !$item->isDot() &&
                    (string) $item->current() != '_MTN') {
                    $folders[(string) $item->current()] = (string) $item->current();
                    if ($levels <> 1) {
                        $folders = array_merge($folders, self::getFolders($item->getPathName(), $levels--));
                    }
                }
            }
        } catch (Exception $e) { }
        return $folders;
   }

   public static function getFiles($path, $levels=0, $rel=false)
   {
       $rel=false;
       $files = array();
       if ($rel === true) {
           $base = $path;
           $parent = '';
       } elseif ($rel=== false) {
           $base = $path;
           $parent = false;
       } else {
           $base = !empty($rel) ? $rel . '/' : '' . basename($path);
           $parent = $base;
       }
       $exts = array('js', 'css', 'xml', 'xt');
       try {
            foreach (new DirectoryIterator($path) as $item) {
                if ($item->isFile() && !$item->isDot() &&
                    in_array(pathinfo($item, PATHINFO_EXTENSION), $exts)) {
                    $fileName = (string) $item->current();
                    $files[$base][$fileName] = $item->getPathName();
                } elseif ($levels <> 1 &&
                    $item->isDir() && !$item->isDot()) {
                    $files = array_merge_recursive($files, self::getFiles($item->getPathName(), $levels--, $parent));
                }
            }
        } catch (Exception $e) { }
        return $files;
    }

/**
 * Register function
 *
 * Register css in queue for later rendering
 *
 * @author Andy Varganov <andyv@xaraya.com>
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params array  $args array of optional parameters<br/>
 *         string $args[scope] scope of style, one of common!theme(default)|module|block|property<br/>
 *         string $args[method] style method, one of link(default)|import|embed<br/>
 *         string $args[alternatedir] alternative base folder to look in, falling back to...<br/>
 *         string $args[base] base folder to look in, optional, default "style"<br/>
 *         string $args[file] name of file required for link or embed methods, optional, default "style"<br/>
 *         string $args[filext] extension to use for file(s), optional, default "css"<br/>
 *         string $args[source] source code, required for embed method, default ""<br/>
 *         string $args[alternate] switch to set rel="alternate stylesheet", optional true|false(default)<br/>
 *         string $args[rel] rel attribute, optional, default "stylesheet"<br/>
 *         string $args[type] link/style type attribute, optional, default "text/css"<br/>
 *         string $args[media] media attribute, optional, default "screen"<br/>
 *         string $args[title] title attribute, optional, default ""<br/>
 *         string $args[condition] conditionals for ie browser, optional, default ""<br/>
 *         string $args[theme] theme name, optional first theme to look for in theme scope
 *         string $args[module] module for module|block scope, optional, default current module<br/>
 *         string $args[property] standalone property name, required for property scope
 *         string $args[block] standalone block name, required for block scope
 * @todo: support other W3C standard attributes of link and style tags?
 * @return boolean true on success
 * @throws none
 *
**/
    public function register($args)
    {
        extract($args);

        // set some defaults
        if (!isset($method)) // link|import|embed
            $method = 'link';

        // if method is embed we need a source
        if ($method == 'embed' && empty($source)) return;

        if (!isset($scope)) // common|theme|module|block|property
            $scope = 'module';

        // if scope is property we need a property name
        if ($scope == 'property' && empty($property)) return;

        // init tag from args / defaults
        $tag = array(
            'method'     => $method,
            'scope'      => $scope,
            'base'       => !empty($base)      ? xarVarPrepForOS($base) : xarCSS::CSSCOMMONBASE,
            'file'       => !empty($file)      ? $file      : xarCSS::CSSCOMMONFILE,
            'fileext'    => !empty($fileext)   ? $fileext   : xarCSS::CSSCOMMONFILEEXT,
            'type'       => !empty($type)      ? $type      : xarCSS::CSSTYPETEXT,
            'media'      => !empty($media)     ? $media     : xarCSS::CSSMEDIASCREEN,
            'rel'        => !empty($rel)       ? $rel       : xarCSS::CSSRELSTYLESHEET,
            'source'     => !empty($source)    ? $source    : '',
            'title'      => !empty($title)     ? $title     : '',
            'condition'  => !empty($condition) ? $condition : '',
            'theme'      => '',
            'module'     => '',
            'property'   => '',
            'block'      => '',
            'url'        => '',
            'alternatedir' => !empty($alternatedir) ? xarVarPrepForOS($alternatedir) : '',
        );

        // Local or remote absolute url, just include it and return
        // We support this above all for testing third party stuff we may later integrate
        $server = xarServer::getHost();
        if (($tag['method'] == "link") && !empty($tag['source'])) {
            $tag['url'] = $tag['source'];
            return $this->queue($method, $scope, $tag['url'], $tag);
        }
        
        // set additional params based on method
        switch ($method) {
            case 'embed':
                // embed method, we're done, queue the source and bail
                return $this->queue($method, $scope, $tag['source'], $tag);
                break;
            case 'import':
                $tag['media'] = str_replace(' ', ', ', $tag['media']);
                break;
            case 'link':
                if (isset($alternate) && $alternate == 'true') {
                    if (empty($rel)) // 'alternate stylesheet'
                        $tag['rel'] = xarCSS::CSSRELALTSTYLESHEET;
                }
                break;
        }

        if ($scope == 'common' && empty($file))
            $tag['file'] = xarCSS::CSSCOMMONCORE;

        // set common paths to look in
        $fileName = $tag['file'] . '.' . $tag['fileext'];
        $themeDir = xarTpl::getThemeDir();
        $commonDir = xarTpl::getThemeDir('common');
        $codeDir = sys::code();

        $paths = array();
        // if an alternatedir was supplied, look there first
        if (!empty($alternatedir)) {
            // themes/theme/alternate
            $paths[] = $themeDir . '/' . $alternatedir . '/' . $fileName;
            // themes/common/alternate
            $paths[] = $commonDir . '/' . $alternatedir . '/' . $fileName;
        }
        switch ($scope) {
            case 'common':
            case 'theme':
                if (!empty($theme)) {
                    // themes/theme/style
                    $paths[] = xarTpl::getThemeDir($theme) . '/' . $tag['base'] . '/' . $fileName;
                    $tag['theme'] = $theme;
                }
                // themes/theme/style
                $paths[] = $themeDir . '/' . $tag['base'] . '/' . $fileName;
                // themes/common/style
                $paths[] = $commonDir . '/' . $tag['base'] . '/' . $fileName;
                break;
            case 'block':
                if (!empty($block)) {
                    $tag['block'] = $block;
                    $block = xarVarPrepForOS($block);
                    // themes/theme/blocks/block/style
                    $paths[] = $themeDir . '/blocks/' . $block . '/' . $tag['base'] . '/' . $fileName;
                    // themes/common/blocks/block/style
                    $paths[] = $commonDir . '/blocks/' . $block . '/' . $tag['base'] . '/' . $fileName;
                    // code/blocks/block/xartemplates/style
                    $paths[] = $codeDir . 'blocks/' . $block . '/xartemplates/' . $tag['base'] . '/' . $fileName;
                    break;
                }
                if (empty($module))
                    $module = xarVarGetCached('Security.Variables', 'currentmodule');
            case 'module':
                if (empty($module))
                    $module = xarMod::getName();
                $modInfo = xarMod::getBaseInfo($module);
                if (!isset($modInfo)) return;
                $tag['module'] = $module;
                $modOsDir = $modInfo['osdirectory'];
                
                // Handle legacy calls to styles in base module now located in common/style
                if ($module == 'base') {
                    // themes/theme/style
                    $paths[] = $themeDir . '/' . $tag['base'] . '/' . $fileName;
                    // themes/common/style
                    $paths[] = $commonDir . '/' . $tag['base'] . '/' . $fileName;
                }
                
                // themes/theme/modules/module/style
                $paths[] = $themeDir . '/modules/' . $modOsDir . '/' . $tag['base'] . '/' . $fileName;
                // themes/theme/modules/module/styles (legacy)
                $paths[] = $themeDir . '/modules/' . $modOsDir . '/styles/' . $fileName;
                // themes/common/modules/module/style
                $paths[] = $commonDir . '/modules/' . $modOsDir . '/' . $tag['base'] . '/' . $fileName;
                // code/modules/module/xarstyles (legacy)
                $paths[] = $codeDir . 'modules/' . $modOsDir . '/xarstyles/' . $fileName;
                // code/modules/module/xartemplates/style
                $paths[] = $codeDir . 'modules/' . $modOsDir . '/xartemplates/' . $tag['base'] . '/' . $fileName;
                break;
            case 'property':
                $tag['property'] = $property;
                $property = xarVarPrepForOS($property);
                // themes/theme/properties/property/style
                
                $paths[] = $themeDir . '/properties/' . $property . '/' . $tag['base'] . '/' . $fileName;
                // themes/common/properties/property/style
                $paths[] = $commonDir . '/properties/' . $property . '/' . $tag['base'] . '/' . $fileName;
                // code/properties/property/xarstyles
                $paths[] = $codeDir . 'properties/' . $property . '/xarstyles/' . $fileName;
                // code/properties/property/xartemplates/style
                $paths[] = $codeDir . 'properties/' . $property . '/xartemplates/' . $tag['base'] . '/' . $fileName;
                break;
        }
        if (empty($paths)) return;

         // Debug display
         if (xarModVars::get('themes','debugmode') && 
         in_array(xarUser::getVar('id'),xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
            foreach ($paths as $path) {
                echo xarML('Possible location: ') . $path . "<br/>";                
            }
         }

        foreach ($paths as $path) {
            if (!file_exists($path)) continue;
            $filePath = $path;
            // Debug display
             if (xarModVars::get('themes','debugmode') && 
             in_array(xarUser::getVar('id'),xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
                echo "<b>" . xarML('Chosen: ') . $path . "</b><br/>";
             }
            break;
        }
        if (empty($filePath)) return;
        
        // Turn relative path into an absolute URL
        $webDir = sys::web();
        if (!empty($webDir) && strpos($filePath, $webDir) === 0) {
            $filePath = substr($filePath, strlen($webDir));
        }
        $filePath = xarServer::getBaseURL() . $filePath;
        $tag['url'] = $filePath;

        return $this->queue($method, $scope, $tag['url'], $tag);

    }

/**
 * Queue function
 *
 * Add css to queue
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @param string  $scope the scope of the file (common, theme, module, block, property)
 * @param string  $method the method to use (link, import, embed)
 * @param string  $url source, either code to embed or url of file to link or import
 * @param array   $data tag data to cache
 * @return boolean true on success
 * @todo make private once xarTpl functions are deprecated
**/
    public function queue($method, $scope, $url, $data)
    {
        if (empty($scope) || empty($method) || empty($url) || empty($data)) return;

        // keep track of style when we're caching
        xarCache::addStyle($data);

        // init the queue
        if (!isset(self::$css)) {
            // scope rendering order...
            $scopes = array(
                'common'   => array(),
                'theme'    => array(),
                'module'   => array(),
                'block'    => array(),
                'property' => array(),
            );
            // method rendering order...
            self::$css = array(
                'import' => $scopes,
                'link'   => $scopes,
                'embed'  => $scopes,
            );
            unset($scopes);
        }
        // skip unknown scopes/methods (for now)
        if (!isset(self::$css[$method][$scope])) return;

        // hash the url to prevent the same source code
        // or file name being included more than once
        $index=md5($url);

        // queue the style
        self::$css[$method][$scope][$index] = $data;

        return true;
    }

/**
 * Render function
 *
 * Render queued css
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params array   $args array of optional parameters<br/>
 *         boolean $args[comments] show comments, optional, default false
 * @todo option to turn on/off style comments in UI, cfr template comments
 * @return string templated output of css to render
 * @throws none
**/
    public function render($args)
    {
        if (empty(self::$css)) return;
        extract($args);
        if ($this->combined) {
            $this->combine();
        }
        $args['styles'] =& self::$css;
        $args['comments'] = !empty($comments);

        return xarTpl::module('themes', 'css', 'render', $args);
    }

/**
 * Combine CSS
 *
 * Takes the content of queued css files and embedded source code,
 * or @imported styles contained within other stylesheets and combines
 * them into a single stylesheet
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access private
 * @params none
 * @throws none
 * @return bool true on success
 * @todo implement proper caching using xarCache
**/
    private function combine()
    {
        if (empty(self::$css) || !$this->combined) return;
        $content = '';
        foreach (self::$css as $method => $scopes) {
            if (empty($scopes)) continue;
            foreach ($scopes as $scope => $styles) {
                if (empty($styles)) continue;
                foreach ($styles as $index => $style) {
                    if (empty($style)) continue;
                    if (($style['media'] != 'all' && $style['media'] != 'screen') ||
                        !empty($style['condition'])) continue;
                    if ($style['method'] != 'embed') {
                        $string = @file_get_contents($style['url']);
                        if (empty($string)) continue;
                        if ($this->compressed) {
                            $string = $this->compress($string, $style['url']);
                        } else {
                            $string = $this->fixurlpaths($string, $style['url']);
                            $string = $this->combineimports($string, $style['url']);
                        }
                        //if (empty($string)) continue;
                        $content .= "/* Combined CSS from file $style[url] */\n\n";
                        $content .= $string;
                    } else {
                        if ($this->compressed) {
                            $string = $this->compress($style['source']);
                        } else {
                            $string = $style['source'];
                        }
                        //if (empty($string)) continue;
                        $content .= "/* Combined embedded CSS */\n\n";
                        $content .= $string;
                    }
                    $content .= "\n\n";
                    // remove combined css from queue
                    // @todo: this should be queued and only removed when the file is written
                    unset(self::$css[$method][$scope][$index]);
                }
            }
        }
        if (empty($content)) return;
        // @todo: implement proper caching
        $cacheKey = md5($content);
        $filePath = sys::varpath() . '/' . $this->cacheDir . '/' . $cacheKey . '.css';
        if (!file_exists($filePath)) {
            $fp = @fopen($filePath,'wb');
            if (!$fp) return;
            $size = fwrite($fp, $content);
            if (!$size || $size < strlen($content)) return;
            fclose($fp);
        }

        // Turn relative path into an absolute URL
        $webDir = sys::web();
        if (!empty($webDir) && strpos($filePath, $webDir) === 0) {
            $filePath = substr($filePath, strlen($webDir));
        }
        $filePath = xarServer::getBaseURL() . $filePath;

        // Queue the combined stylesheet
        $index = md5($cacheKey . '.css');
        self::$css['link']['theme'][$index] = array(
            'method' => 'link',
            'scope' => 'theme',
            'rel' => 'stylesheet',
            'title' => 'Combined Styles',
            'url' => $filePath,
            'type' => 'text/css',
            'media' => 'all',
            'condition' => '',
        );
        return true;
    }

/**
 * Compress CSS
 *
 * Compress CSS (when combining and caching)
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access private
 * @param  string css to compress
 * @throws none
 * return  string compressed css
**/
    private function compress($string='', $fileName='')
    {
        if (empty($string)) return '';
        // remove comments
        $string = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $string);
        // remove tabs, spaces, newlines, etc.
        $string = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $string);
        if (!empty($fileName)) {
            // replace relative paths like url(../images/somefile.png)
            $string = $this->fixurlpaths($string, $fileName);
            // combine any @imports from this file
            $string = $this->combineimports($string, $fileName);
        }
        return $string;
    }

/**
 * Fix url paths
 *
 * transform paths relative to current file into paths relative to web root
 * eg, url(../images/myfile.png) in file /themes/common/style/style.css
 * will be transformed into url(/themes/common/images/myfile.png);
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access private
 * @param  string $string the string to look in for replacements
 * @param  string $fileName the name of the file the string belongs to
 * @throws none
 * return  string the string with urls replaced
**/
    private function fixurlpaths($string, $fileName)
    {
        // remove the domain name from path (if any)
        $base = xarServer::getBaseURL();
        if (strpos($fileName, $base) === 0) {
            $fileName = str_replace($base, '', $fileName);
        }
        // add leading slash if required so url is relative to web root
        if (strpos($fileName, '/') !== 0) {
            $fileName = '/'.$fileName;
        }
        // get the directory the file declaring the url lives in
        $filePath = dirname($fileName);
        // find all url() declarations
        preg_match_all('!url\([\'|"]?([^\'|"|\)]*)[\'|"]?\)!', $string, $matches);
        if (!empty($matches)) {
            foreach ($matches[1] as $i => $match) {
                // skip replacements on paths already relative to web root
                if (strpos($match, '/') === 0) continue;
                $curPath = $filePath;
                // see if the declaration is relative to current file directory
                $count = substr_count($match,'../');
                if (!empty($count)) {
                    while ($count > 0) {
                        // move up the path once for each occurence of ../
                        $curPath = dirname($curPath);
                        $count--;
                    }
                }
                // replace all occurences of ../ in the url
                $match = str_replace('../', '', $match);
                // replace the url with the full path relative to web root
                $string = str_replace($matches[0][$i], "url($curPath/$match)", $string);
            }
        }
        return $string;
    }

/**
 * Combine imports
 *
 * embeds css from @import url() into combined stylesheet
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access private
 * @param  string $string the string to look in for replacements
 * @param  string $fileName the name of the file the string belongs to
 * @throws none
 * return  string the string with @imports replaced with content
**/
    private function combineimports($string, $fileName)
    {
        if (preg_match_all('!@import\s*url\([\'|"]?([^\'|"|\)]*)[\'|"]?\);!', $string, $matches)) {
            foreach ($matches[1] as $i => $match) {
                if (strpos($match, '/') === 0) {
                    $match = substr($match, 1, strlen($match));
                }
                $ifile = @file_get_contents($match);
                if (empty($ifile)) continue;
                if ($this->compressed) {
                    $ifile = $this->compress($ifile, $match);
                } else {
                    $ifile = $this->fixurlpaths($ifile, $match);
                    $ifile = $this->combineimports($ifile, $match);
                }
                $content = "/* Replaced @import url($match) in file $fileName */\n\n";
                $content .= $ifile;
                $content .= "\n\n";
                $string = str_replace($matches[0][$i], $content, $string);
            }
        }
        return $string;
    }

    // prevent cloning of singleton instance
    public function __clone()
    {
        throw new ForbiddenException();
    }
}

/**
 * Base CSS Lib Class
 *
 * This object models a CSS Framework
**/
class xarCSSLib extends Object
{
    // required meta data, filled in when the object is created
    public $name;
    public $displayname;
    public $description;
    public $osdirectory;

    // All the optional meta data for this library
    public $script        = array(); // default script
    public $style         = array(); // default style
    public $scriptfolder  = '';      // where to look for lib scripts
    public $pluginfolder  = 'plugins';      // where to look for plugins, relative to base folder
    public $stylefolder   = '';      // where to look for styles, relative to script folder
    public $versions      = array(); // array of known versions
    public $dependencies  = array(); // array of lib dependencies
    public $events        = array(); // array of events supplied by lib

    // Library files
    public $styles        = array(); // all styles

    public function __construct($name)
    {
        if (empty($name))
            throw new BadParameterException($name, 'Invalid name "#(1)" for xarCSSLib');
        // first run, populate the library meta data
        $this->name = $name;
        $this->displayname = ucfirst($this->name);
        $this->description = xarML('#(1) CSS Framework', $this->displayname);
        $this->osdirectory = xarVarPrepForOS($this->name);
    }

/**
 * Find library files
 * The intent here is to scan the entire filesystem looking for files
 * and folders belonging to this library
**/
    public function findFiles()
    {
        // we want to look in all active themes
        $themes = xarMod::apiFunc('themes', 'admin', 'getlist',
            array('filter' => array('Class' => 2, 'State' => XARTHEME_STATE_ACTIVE)));
        // we want to look in all active modules
        $modules = xarMod::apiFunc('modules', 'admin', 'getlist',
            array('filter' => array('State' => XARMOD_STATE_ACTIVE)));
        // set default paths and filenames
        $libName     = $this->name;
        $baseDir     = xarTpl::getBaseDir();
        $themeDir    = xarTpl::getThemeDir();
        $themeName   = xarTpl::getThemeName();
        $commonDir   = xarTpl::getThemeDir('common');
        $codeDir     = sys::code();
        $libBase     = xarCSS::LIB_BASE;
        $libBaseAlt  = xarCSS::LIB_BASE_ALT;

        $paths = array();
        $themes[] = array('osdirectory' => 'common');
        // first we want to look in each active theme...
        foreach ($themes as $theme) {
            $themeOSDir = $theme['osdirectory'];
            // look in themes/<theme>/lib/libname/*
            $paths['theme'][$themeOSDir] = "{$baseDir}/{$themeOSDir}/{$libBase}/{$libName}";
            // then in each active module, this theme
            foreach ($modules as $mod) {
                $modOSDir = $mod['osdirectory'];
                // look in themes/<theme>/modules/<module>/lib/libname/*
                $paths['module'][$modOSDir] = "{$baseDir}/{$themeOSDir}/modules/{$modOSDir}/{$libBaseAlt}/{$libName}";
            }
        }
        // now we look in each active module
        foreach ($modules as $mod) {
            $modOSDir = $mod['osdirectory'];
            // look in code/modules/<module>/xartemplates/lib/libname/*
            $paths['module'][$modOSDir] = "{$codeDir}modules/{$modOSDir}/{$libBaseAlt}/{$libName}";
        }
        
        // Load the version class to check versions
        sys::import('xaraya.version');
        
        // find files in all lib folders, all themes, all modules, all properties
        $this->scripts = array();
        $this->styles = array();
        foreach ($paths as $scope => $packages) {
            foreach ($packages as $package => $path) {
                if (!is_dir($path)) continue;
                $versions = xarCSS::getFolders($path, 1);
                if (empty($versions)) continue;
                foreach (array_keys($versions) as $version) {
                    // Check if this is a valid version folder
                    $valid = xarVersion::parse($version);
                    if(!$valid) continue;
                    
                    $subpath = $path . "/" . $version;
//                echo "<pre>";var_dump($subpath);//exit;
                    $files = xarCSS::getFiles($subpath);
                    if (empty($files)) continue;
                    foreach ($files as $folder => $items) {
                        foreach ($items as $file => $filepath) {
                            // store script as scope - package - libbase/libname - file
                            // eg, scripts[theme][common][lib/jquery][jquery-1.4.4.min.js] =
                            // /themes/common/lib/jquery/jquery-1.4.4.min.js
                            // init the actual tag info used to init this lib
                            $tag = array(
                                'lib'    => $libName,
                                'scope'  => $scope,
                                'type'   => 'lib',
                                'origin' => 'local',
                            );
                            switch ($scope) {
                                case 'theme':
                                case 'common':
                                    $tag['theme'] = $package;
                                break;
                                case 'module':
                                case 'block':
                                    $tag['module'] = $package;
                                break;
                                case 'property':
                                    $tag['property'] = $package;
                                break;
                            }
                            $ext = pathinfo($file, PATHINFO_EXTENSION);
                            switch ($ext) {
                                case 'js':
                                    $tag['src'] = $file;
                                    $tag['version'] = $version;
                                    $tag['package'] = $package;
                                    $base = "{$libBase}/{$libName}";
                                    // remove the filename from the path
                                    $basepath = str_replace("/$file", '', $filepath);
                                    // remove anything before the base
                                    $basepath = preg_replace("!^.*".$base."+(.*)$!", $base."$1", $basepath);
                                    // if this isn't base, keep everything after base
                                    if ($basepath != $base)
                                        $base = preg_replace("!^.*".$base."+(.*)$!", $base."$1", $basepath);
                                    $tag['base'] = $base;
                                    $this->scripts[$version][$scope][$package][$base][$file] = $tag;
//                                    var_dump($this->scripts);
                                break;
                                case 'css':
                                    $tag['file'] = $file;
                                    $tag['version'] = $version;
                                    $tag['package'] = $package;
                                    $base = "{$libBase}/{$libName}";
                                    // remove the filename from the path
                                    $basepath = str_replace("/$file", '', $filepath);
                                    // remove anything before the base
                                    $basepath = preg_replace("!^.*".$base."+(.*)$!", $base."$1", $basepath);
                                    // if this isn't base, keep everything after base
                                    if ($basepath != $base)
                                        $base = preg_replace("!^.*".$base."+(.*)$!", $base."$1", $basepath);
                                    $tag['base'] = $base;
                                    $this->styles[$version][$scope][$package][$base][$file] = $tag;
//                                    var_dump($this->styles);
                                break;
                                case 'xt':
                                    $tag['template'] = str_replace('.xt', '', $file);
                                    $this->templates[$version][$scope][$package][$base][$file] = $tag;
                                break;
                                case 'xml':
                                break;
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
