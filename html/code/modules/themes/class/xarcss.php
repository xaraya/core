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

    // experimental combine/compress options
    private $cacheDir   = 'cache/css';
    private $combined   = true;
    private $compressed = true;

    // prevent direct creation of this object
    private function __construct()
    {
        $this->combined   = xarModVars::get('themes', 'css.combined');
        $this->compressed = xarModVars::get('themes', 'css.compressed');
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

        // Local absolute url, just include it and return
        // We support this above all for testing third party stuff we may later integrate
        $server = xarServer::getHost();
        if (($tag['method'] == "link") && !empty($tag['source']) &&
            preg_match("!://($server|localhost|127\.0\.0\.1)(:\d+|)/!",$tag['source'])) {
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
         in_array(xarUserGetVar('uname'),xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
            foreach ($paths as $path) {
                echo xarML('Possible location: ') . $path . "<br/>";                
            }
         }

        foreach ($paths as $path) {
            if (!file_exists($path)) continue;
            $filePath = $path;
            // Debug display
             if (xarModVars::get('themes','debugmode') && 
             in_array(xarUserGetVar('uname'),xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
                echo xarML('Chosen: ') . $path . "<br/>";
             }
            break;
        }
        if (empty($filePath)) return;

        $tag['url'] = $filePath; //xarServer::getBaseURL() . $filePath;

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
        //print_r(self::$css);
        if (empty(self::$css)) return;
        extract($args);
        if ($this->combined) {
            $this->combine();
        }
        $args['styles'] = self::$css;
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
?>
