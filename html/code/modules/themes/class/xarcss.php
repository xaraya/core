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
// import the base themes class
sys::import('modules.themes.class.xarthemes');
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
        
    // prevent direct creation of this object
    private function __construct()
    {
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
            $scope = 'theme';
        
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
            'url'        => '',
            'alternatedir' => !empty($alternatedir) ? xarVarPrepForOS($alternatedir) : '',
        );       

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
            case 'module':
                if (empty($module))
                    $module = xarMod::getName();
            case 'block':
                if (empty($module))
                    $module = xarVarGetCached('Security.Variables', 'currentmodule');
                $modInfo = xarMod::getBaseInfo($module);
                if (!isset($modInfo)) return;
                $tag['module'] = $module;
                $modOsDir = $modInfo['osdirectory'];
                // handle legacy calls to styles in base module now located in common/style
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
                // code/properties/property/xartemplates/style
                $paths[] = $codeDir . 'properties/' . $property . '/xartemplates/' . $tag['base'] . '/' . $fileName;
                break;
        }
        if (empty($paths)) return;
        
        foreach ($paths as $path) {
            if (!file_exists($path)) continue;
            $filePath = $path;
            break;
        }
        if (empty($filePath)) return;
        
        $tag['url'] = xarServer::getBaseURL() . $filePath;
        
        return $this->queue($method, $scope, $tag['url'], $tag);
        
    }

/**
 * Render function
 * 
 * Render queued css
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params array   $args array of optional paramaters<br/>
 *         boolean $args[comments] show comments, optional, default false
 * @todo option to turn on/off style comments in UI, cfr template comments
 * @return string templated output of css to render
 * @throws none
**/
    public function render($args)
    {
        if (empty(self::$css)) return;
        extract($args);
        
        $args['styles'] = self::$css;
        $args['comments'] = !empty($comments);
        
        return xarTpl::module('themes', 'css', 'render', $args);
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

    // prevent cloning of singleton instance
    public function __clone()
    {
        throw new ForbiddenException();
    }
}
?>