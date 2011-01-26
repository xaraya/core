<?php
/**
 * Xaraya JavaScript class library
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
 * Base JS Class
**/
class xarJS extends Object
{
/**
 * Defines for this library
 *
 * @author Chris Powis   <crisp@xaraya.com>
 * @todo evaluate if these are really necessary
**/
    const JSCOMMONBASE             = 'scripts';
    const JSLIBBASE                = 'lib';
    
    private static $instance;
    private static $js;

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
 * Register javascript in the queue for later rendering
 *
 * @author Jason Judge
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @param  array   $args array of optional parameters<br/>
 *         string  $args[position] position to render the js, eg head or body, optional, default head<br/>
 *         string  $args[type] type of js to include, either src or code, optional, default src<br/>
 *         string  $args[code] code to include if type is code<br/>
 *         mixed   $args[filename] array containing filename(s) or string comma delimited list
 *                 name of file(s) to include, required if type is src, or<br/> 
 *                 file(s) to get contents from if type is code and code isn't supplied<br/>
 *         string  $args[module] name of module to look for file(s) in, optional, default current module<br/>
 *         string  $args[index] optional index in queue relative to other scripts<br/>
 * @return boolean true on success
 * @throws none
**/
    public function register($args)
    {
        extract($args);        

        // set some defaults
        if (empty($position)) 
            $position = 'head';        

        if (empty($type)) 
            $type = 'src';

        if (empty($scope))
            $scope = 'theme'; 
        
        if ($scope == 'property' && empty($property)) return;

        // init tag from args / defaults
        $tag = array(
            'position'  => $position,
            'type'      => $type,
            'scope'     => $scope,
            'base'      => xarJS::JSCOMMONBASE,
            'filename'  => '',
            'code'      => !empty($code) ? $code : '',
            'module'    => '',
            'property'  => '',
            'url'       => '',
        );

        // set additional params based on type
        switch ($type) {
            case 'code':
                if (!empty($code))
                    return $this->queue($position, $type, $scope, $code, $tag);
                // if code isn't present we get it from a file, fall through to src type
            case 'src':
                if (empty($filename)) return;
                break;
        }

        // set additional params based on scope
        switch ($scope) {
            case 'theme':
                $package = '';
                break;
            case 'module':
                $tag['module'] = $package = !empty($module) ? $module : xarMod::getName();
                break;
            case 'block':
                $tag['module'] = $package = empty($module) ? xarVarGetCached('Security.Variables', 'currentmodule') : $module; 
                break;
            case 'property':
                $tag['property'] = $package = $property;
                break;
        }

        // if we're here, we have files to look for
        $files = !is_array($filename) ? explode(',', $filename) : $filename;        

        foreach ($files as $file) {
            // break off any params 
            if (strpos($file, '?') !== false) 
                list($file, $params) = explode('?', $file, 2);
            // get path relative to web root            
            $relPath = $this->findFile($scope, trim($file), $tag['base'], $package, true);
            if (empty($relPath)) continue;
            // if type is code, we want the file contents
            if ($type == 'code') {
                $code = file_get_contents($relPath);
                if (empty($code)) continue;
                $tag['code'] = $code;
            }
            // fill in the other tag parameters
            $tag['filename'] = $file;
            $filePath = xarServer::getBaseURL() . $relPath;
            if (!empty($params)) {
                $filePath .= '?'.$params;
                unset($params);
            }
            $tag['url'] = $filePath;
            // queue the tag
            $this->queue($position, $type, $scope, $tag['url'], $tag);
        }
        
        return true;
    }

/**
 * Render function
 *
 * Render queued javascript
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @param array   $args array of optional parameters<br/>
 *        string  $args[position] position to render, optional<br/>
 *        string  $args[index] index to render, optional<br/>
 *        string  $args[type] type to render, optional
 * @return string templated output of js to render
 * @throws none
**/    
    public function render($args)
    {
        $javascript = $this->getQueued($args);
        if (empty($javascript)) return;  
        $args['javascript'] = $javascript;
        $args['comments'] = !empty($args['comments']);
        return xarTpl::module('themes', 'javascript', 'render', $args);
    }

/**
 * Get Queued function
 *
 * Get queued JS, optionally by position, index
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @param array   $args array of optional parameters<br/>
 *        string  $args[position] position to get JS for, optional<br/>
 *        string  $args[type] type to get JS for, optional
 *        string  $args[scope] scope of data source, optional
 * @return mixed array of queued js, false if none found
 * @throws none
**/
    public function getQueued($args)
    {
        extract($args);
        $javascript = array();
        if (!empty($position) && !empty($type) && !empty($scope) && 
            isset(self::$js[$position][$type][$scope])) {
            $javascript[$position][$type][$scope] = self::$js[$position][$type][$scope];
        } elseif (!empty($position) && !empty($type) && 
            isset(self::$js[$position][$type])) {
            $javascript[$position][$type] = self::$js[$position][$type];
        } elseif (!empty($position) &&
            isset(self::$js[$position])) {
            $javascript[$position] = self::$js[$position];
        } elseif (isset(self::$js)) {
            $javascript = self::$js;
        }
        if (empty($javascript)) return;
        return $javascript;
    }

/**
 * Queue function
 *
 * Add javascript to queue
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @param string  $position position to place js, [(head)|body], required
 * @param string  $type type of data to queue, [framework|plugin|event|(src)|code], required
 * @param string  $scope scope of data source [(theme)|module|block|property]
 * @param string  $url url to file, or source code to include
 * @param array   $data tag data to queue
 * @param string  $index index to use, optional
 * @return boolean true on success
**/
    public function queue($position, $type, $scope, $url, $data, $index='')
    {
        if (empty($position) || empty($type) || empty($scope) || empty($url) || empty($data)) return;
        
        // keep track of javascript when we're caching
        xarCache::addJavascript($data);
        
        if (!isset(self::$js)) {
            // scope rendering order
            $scopes = array(
                'theme' => array(),
                'module' => array(),
                'block' => array(),
                'property' => array(),
            );
            // head rendering order
            $head = array(
                'lib' => $scopes,
                'plugin' => $scopes,
                'src' => $scopes,
                'code' => $scopes,
                'event' => $scopes,
            );
            // body rendering order
            $body = array(
                'src' => $scopes,
                'code' => $scopes,
            );                        
            self::$js = array(
                'head' => $head,
                'body' => $body,
            );
            unset($scopes); unset($head); unset($body);
        }
        // skip unknown position/type/scope (for now)
        if (!isset(self::$js[$position][$type][$scope])) return;
        
        if (empty($index))
            $index = md5($url);
        
        self::$js[$position][$type][$scope][$index] = $data;
        return true;
    }

/**
 * Find file function
 *
 * Returns the full URL or relative path from webroot to a file
 * obeying standard template cascade paths
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access private
 * @param  string  $scope the scope in which to look for files, required
 * @param  string  $file the name of the file to look for, required
 * @param  string  $base optional sub folder to look in
 * @param  string  $package the name of the theme, module or property to look in<br/>
 *                 Optional in module scope, default current module<br/>
 *                 Required in property scope
 * @return string path to file if found, empty otherwise
 * @throws none
**/
    private function findFile($scope, $file, $base, $package='')
    {
        if (empty($scope) || empty($file) || empty($base)) return;
        
        $themeDir = xarTpl::getThemeDir();
        $commonDir = xarTpl::getThemeDir('common');
        $codeDir = sys::code();

        $paths = array();        
        switch ($scope) {
            case 'theme':
                // themes/theme/scripts
                $paths[] = $themeDir . '/' . $base . '/' . $file;
                // themes/common/scripts
                $paths[] = $commonDir . '/' . $base . '/' . $file;
                break;
            case 'module':
                if (empty($package))
                    $package = xarMod::getName();
                $modInfo = xarMod::getBaseInfo($package);
                if (!isset($modInfo)) return;
                $modOsDir = $modInfo['osdirectory'];
                // support legacy calls to base module scripts now moved to common/scripts
                if ($package == 'base') {
                    // themes/theme/scripts
                    $paths[] = $themeDir . '/' . $base . '/' . $file;
                    // themes/common/scripts
                    $paths[] = $commonDir . '/' . $base . '/' . $file;
                }
                // themes/theme/modules/module/scripts
                $paths[] = $themeDir . '/modules/' . $modOsDir . '/' . $base . '/' . $file;
                // themes/theme/modules/module/includes (legacy)
                $paths[] = $themedir . '/modules/' . $modOsDir . '/includes/' . $file;
                // themes/theme/modules/module/xarincludes (legacy)
                $paths[] = $themedir . '/modules/' . $modOsDir . '/xarincludes/' . $file;
                // themes/common/modules/module/scripts
                $paths[] = $commonDir . '/modules/' . $modOsDir . '/' . $base . '/' . $file;
                // code/modules/module/xartemplates/scripts
                $paths[] = $codeDir . 'modules/' . $modOsDir . '/xartemplates/' . $base . '/' . $file;
                // code/modules/module/xartemplates/includes (legacy)
                $paths[] = $codeDir . 'modules/' . $modOsDir . '/xartemplates/includes/' . $file;
                break;
            case 'property':
                if (empty($package)) return;
                // themes/theme/properties/property/scripts
                $paths[] = $themeDir . '/properties/' . $package . '/' . $base . '/' . $file;
                // themes/common/properties/property/scripts
                $paths[] = $commonDir . '/properties/' . $package . '/' . $base . '/' . $file;
                // code/properties/property/xartemplates/scripts
                $paths[] = $codeDir . 'properties/' . $package . '/xartemplates/' . $base . '/' . $file;
                break;
         }
         if (empty($paths)) return;
         
         foreach ($paths as $path) {
             if (!file_exists($path)) continue;
             $filePath = $path;
             break;
         }
         if (empty($filePath)) return;
         
         return $filePath;
    }
  
    // prevent cloning of singleton instance
    public function __clone()
    {
        throw new ForbiddenException();
    }
    
}
?>