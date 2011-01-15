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
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
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
**/
    const CSSRELSTYLESHEET         = "stylesheet";
    const CSSRELALTSTYLESHEET      = "alternate stylesheet";
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
    const CSSCOMMON                = "common";
    const CSSCOMMONSOURCE          = "xarcore-xhtml1-strict";
    const CSSCOMMONBASE            = "base";
    const CSSCOMMONCORE            = "core";

    private static $instance;
    private static $css;
    
    public $themesDir;
    public $themeDir;
    public $commonDir;
    public $codeDir;
    public $baseUrl;
    
    // prevent direct creation of this object
    private function __construct()
    {
        // @todo: evaluate the need for these here, possibly move to register method ?
        // set common filepaths 
        $this->themesDir = xarConfigVars::get(null, 'Site.BL.ThemesDirectory');
        $this->themeDir = xarTplGetThemeDir();
        $commonDir = xarModVars::get('themes', 'themes.common');
        if (empty($commonDir)) $commonDir = xarCSS::CSSCOMMON;
        $this->commonDir = is_dir("{$this->themesDir}/{$commonDir}/") ? "{$this->themesDir}/{$commonDir}/" : "themes/{$commonDir}/";
        $this->codeDir = sys::code();
        $this->baseUrl = xarServer::getBaseURL();
    }   

/**
 * Get instance function
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params none
 * @return Object current instance
 * @throws none
 *
**/
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
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
 *         string $args[base] base folder to look in, default depends on scope<br/>
 *         string $args[file] name of file required for link or embed methods<br/>
 *         string $args[filext] extension to use for file(s), optional, default "css"<br/>
 *         string $args[source] source code, required for embed method, default null<br/>
 *         string $args[alternate] switch to set rel="alternate stylesheet", optional true|false(default)<br/>
 *         string $args[rel] rel attribute, optional, default "stylesheet"<br/>
 *         string $args[type] link/style type attribute, optional, default "text/css"<br/>
 *         string $args[media] media attribute, optional, default "screen"<br/>
 *         string $args[title] title attribute, optional, default ""<br/>
 *         string $args[condition] conditionals for ie browser, optional, default null<br/>
 *         string $args[module] module for module|block scope, optional, default current module<br/>
 *         string $args[property] property required for property scope   
 * @return boolean true on success
 * @throws none
 *
**/
    public function register($args)
    {
        extract($args);
        
        // set some defaults
        if (!isset($scope)) // common|theme|module|block|property
            $scope = 'theme';

        if (!isset($method)) // link|import|embed
            $method = 'link';
        
        // if method is embed we need a source
        if ($method == 'embed' && empty($source)) return;

        // set default filename 
        if (empty($file)) {
            if ($scope == 'common') {
                // common scope default is core.css
                $file = xarCSS::CSSCOMMONCORE;
            } else {
                // all other scopes default is style.css
                $file = 'style';
            }
        }
        
        // set default file extension
        if (!isset($fileext)) 
            $fileext = 'css';
            
        // set default base
        if (empty($base)) {
            // set defaults based on scope 
            if ($scope == 'common' || $scope == 'theme') {
                $base = 'style';
            } elseif ($scope == 'module') {
                if (empty($module)) 
                    $module = xarMod::getName();
                // handle legacy calls to base module for core styles                
                if ($module == 'base') {
                    $scope = 'theme';
                    $base = 'style';
                } else {
                    $base = $module;
                }
            } elseif ($scope == 'block') {
                if (empty($module)) 
                    $module = xarVarGetCached('Security.Variables', 'currentmodule');               
                $base = $module;
            } elseif ($scope == 'property') {
                // no property, bail
                if (empty($property)) return;
                $base = $property;
            }
        }
        
        if (!isset($source)) // null
            $source = null;
        
        if (isset($alternate) && $alternate == 'true') {
            if (!isset($rel)) // 'alternate stylesheet'
                $rel = xarCSS::CSSRELALTSTYLESHEET;
        }
        if (!isset($rel)) // 'stylesheet'
            $rel = xarCSS::CSSRELSTYLESHEET;
        if (!isset($type)) // 'text/css'
            $type = xarCSS::CSSTYPETEXT;
        if (!isset($media)) // 'screen'
            $media = xarCSS::CSSMEDIASCREEN;
        if ($method == 'import')
            $media = str_replace(' ', ', ', $media);
        if (!isset($title)) // ''
            $title = '';
        if (!isset($condition)) // null
            $condition = null;                

        // build the tag data
        $tag = array(
            'scope' => $scope,
            'method' => $method,
            'base' => $base,
            'file' => $file,
            'fileext' => $fileext,
            'source' => $source,
            'rel' => $rel,
            'type' => $type,
            'media' => $media,
            'title' => $title,
            'condition' => $condition,
            'url' => '',
        );   
        
        // if we're embedding, we're done 
        if ($method == 'embed') {
            // queue the css to embed 
            $tag['url'] = $source;
            return $this->queue($scope, $method, $source, $tag);
        }
        
        // from this point on we're either linking or importing a file

        // name of file       
        $fileName = "{$file}.{$fileext}";
        // initialise css cascade
        $filePaths = array();
        // try alternatedir first, if specified, applies to all scopes              
        if (!empty($alternatedir)) {
            // first in current theme
            $filePaths[] = "{$this->themeDir}/{$alternatedir}/{$fileName}";
            // then in common theme
            $filePaths[] = "{$this->commonDir}/{$alternatedir}/{$fileName}";
        }
        // scope specific file paths to look in
        switch (strtolower($scope)) {
            case 'common':
            case 'theme':
                // try current theme over-ride
                $filePaths[] = "{$this->themeDir}/{$base}/{$fileName}";
                // fall back to common default
                $filePaths[] = "{$this->commonDir}{$base}/{$fileName}";  
                break;                
            case 'module':
            case 'block':
                // try current theme over-ride
                $filePaths[] = "{$this->themeDir}/modules/{$base}/styles/{$fileName}";
                // fall back to common over-ride
                $filePaths[] = "{$this->commonDir}/modules/{$base}/styles/{$fileName}";
                // fall back to module default
                $filePaths[] = "{$this->codeDir}modules/{$base}/xarstyles/{$fileName}";
                break;
            case 'property':
                // try current theme over-ride
                $filePaths[] = "{$this->themeDir}/properties/{$base}/style/{$fileName}";
                // fall back to common over-ride
                $filePaths[] = "{$this->commonDir}/properties/{$base}/style/{$fileName}";
                // fall back to property default
                $filePaths[] = "{$this->codeDir}properties/{$base}/style/{$fileName}";
                break;
        }
        // search cascade for file...
        if (!empty($filePaths)) {
            foreach ($filePaths as $filePath) {
                if (file_exists($filePath)) {
                    $foundFile = $filePath;
                    break;
                }
            }
        }
        // no file, nothing to queue...
        if (empty($foundFile)) return;

        // set the fully qualified url for this style
        $tag['url'] = "{$this->baseUrl}{$foundFile}";
        
        // cache the style
        return $this->queue($scope, $method, $tag['url'], $tag);
    }
/**
 * Render function
 * 
 * Render queued css
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params array   $args array of optional paramaters<br/>
 *         array   $args[scopeorder] order to render scopes, optional<br/>
 *                 default scope rendering order common->theme->module->block->property<br/>
 *         boolean $args[comments] show comments, optional, default false
 * @todo support targetting combination of scope and/or method
 *         string  $args[scope] scope to render, optional, default render all 
 *         string  $args[method] method to render, optional, default all
 *                 default method rendering order link->import->embed
 * @todo option to turn on/off style comments in UI, cfr template comments
 * @return string templated output of css to render
 * @throws none
**/
    public function render($args)
    {
        extract($args);
        // @todo: implement these optional parameters
        if (!isset($scope))
            $args['scope'] = null;
        if (!isset($method))
            $args['method'] = null;
        $args['styles'] = $this->getQueued($args['scope'], $args['method']);
        if (!isset($scopeorder))
            $args['scopeorder'] = null;
        // @todo: make this configurable in UI 
        $args['comments'] = !empty($comments);
        return xarTplModule('themes', 'css', 'render', $args);
    }

/**
 * Get queued function
 *
 * Get queued css, optionally by scope, method
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access  public
 * @param string $scope scope to render, optional, default all
 * @param string $method method to render, optional default all
 * @throws none
 * @return array queued css
 * @todo implement this (see @todo in xarCSS::render()) 
 *
**/
    public function getQueued($scope='', $method='')
    {
        // just return the whole queue for now...
        return self::$css;
    }

/**
 * Queue function
 *
 * Add css to queue
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @param string  $scope the scope (common, theme, module, block, property)
 * @param string  $method the method to use (link, import, embed)
 * @param string  $url source, either code to embed or url to file to link or import  
 * @param string  $data tag data to cache
 * @return boolean true on success
 * @todo make private once xarTpl functions are deprecated
**/
    public function queue($scope, $method, $url, $data)
    {
        if (empty($scope) || empty($method) || empty($url) || empty($data)) return;
        
        // keep track of style when we're caching
        xarCache::addStyle($data);
        
        // init the queue 
        if (!isset(self::$css)) {
            // default method rendering order
            $methods = array('link' => array(), 'import' => array(), 'embed' => array());
            // default scopes and rendering order 
            self::$css = array(
                'common' => $methods,
                'theme' => $methods,
                'module' => $methods,
                'block' => $methods,
                'property' => $methods,
            );
            unset($methods);
        }
        // skip unknown scopes/methods (for now)
        if (!isset(self::$css[$scope][$method])) return;
        
        // hash the url to prevent source code or file name being included more than once
        $hash=md5($url);        
        
        // queue the style
        self::$css[$scope][$method][$hash] = $data;

        return true;
    }   

    // prevent cloning of singleton instance
    public function __clone()
    {
        throw new ForbiddenException();
    }
}
?>