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
// import the base themes class
sys::import('modules.themes.class.xarthemes');
/**
 * Base JS Class
**/
class xarJS extends xarThemes
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
            'package'   => '',
            'url'       => '',
        );

        // set additional params based on scope
        switch ($scope) {
            case 'theme':
                break;
            case 'module':
                $tag['package'] = !empty($module) ? $module : xarMod::getName();
                break;
            case 'block':
                $tag['package'] = empty($module) ? xarVarGetCached('Security.Variables', 'currentmodule') : $module; 
                break;
            case 'property':
                $tag['package'] = $property;
                break;
        }

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
        
        // if we're here, we have files to look for
        $files = !is_array($filename) ? explode(',', $filename) : $filename;        

        foreach ($files as $file) {
            // break off any params 
            if (strpos($file, '?') !== false) 
                list($file, $params) = explode('?', $file, 2);
            // get path relative to web root            
            $relPath = $this->findFile($scope, trim($file), $tag['base'], $tag['package'], true);
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
        xarCache::addJavascript($position, $type, $url, $index);
        
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
  
    // prevent cloning of singleton instance
    public function __clone()
    {
        throw new ForbiddenException();
    }
    
}
?>