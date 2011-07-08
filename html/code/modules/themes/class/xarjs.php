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
    private static $instance;
    private static $queue;

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
 *         string  $args[type] type of js to include, either src or code, optional, default src<br/>
 *         string  $args[code] code to include if $type is code<br/>
 *         mixed   $args[filename] array containing filename(s) or string comma delimited list
 *                 name of file(s) to include, required if $type is src, or<br/> 
 *                 file(s) to get contents from if $type is code and $code isn't supplied<br/>
 *         string  $args[module] name of module to look for file(s) in, optional, default current module<br/>
 *         string  $args[property] name of property to look for file(s) in, optional<br/>
 *         string  $args[position] position to render the js, eg head or body, optional, default head<br/>
 *         string  $args[index] optional index in queue relative to other scripts<br/>
 * @return boolean true on success
 * @throws none
**/
    public function register($args)
    {
        extract($args);        

        if (empty($code) && empty($filename)) return;
        if (empty($type)) $type = 'src';
        if (empty($position)) $position = 'head';
        if (empty($index)) $index = null;
        
        if (!empty($filename)) {
            $files = !is_array($filename) ? explode(',', $filename) : $filename;
        }   

        switch(strtolower($type)) {
    
            case 'code':
                // inline code 
                if (!empty($code)) {
                    // inline code supplied by attribute
                    // Use a hash index to prevent the same JS code fragment
                    // from being included more than once.
                    if (empty($index)) {
                        $index = md5($code);
                    }
                    $this->queue($position, $type, $code, $index);
                } elseif (!empty($files)) {
                    // inline code from file contents
                    if (empty($module))
                        list($module) = xarController::$request->getInfo(); 
                    // No fallback for this param
                    if (empty($property)) $property = '';
                    foreach ($files as $file) {
                        $filePath = $this->findfile(array('filename' => trim($file), 'property' => $property, 'module' => $module));
                        if (empty($filePath)) continue;
                        // get file contents
                        $code = file_get_contents($filePath);
                        if (!$code) continue;
                        // Use a hash index to prevent the same JS code fragment
                        // from being included more than once.
                        if (empty($index)) {
                            $index = md5($code);
                        }
                        $this->queue($position, $type, $code, $index);                  
                    }
                }
                break;
            
            case 'src':
                // include file
                if (empty($module))
                    list($module) = xarController::$request->getInfo();        
                // No fallback for this param
                if (empty($property)) $property = '';
                foreach ($files as $file) {
                    $filePath = $this->findfile(array('filename' => trim($file), 'property' => $property, 'module' => $module));
                    if (empty($filePath)) continue;
                    // Use filepath as index to prevent the same file 
                    // from being included more than once.
                    if (empty($index)) {
                        $index = $filePath;
                    }
                    $this->queue($position, $type, xarServer::getBaseURL() . $filePath, $index);
                }
                break;

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
        return xarTplModule('themes', 'javascript', 'render', $args);
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
 *        string  $args[index] index to get JS for, optional
 * @return mixed array of queued js, false if none found
 * @throws none
**/
    public function getQueued($args)
    {
        extract($args);        
        if (empty($position) && empty($index)) {
            return self::$queue;
        } elseif (!empty($position) && empty($index) && isset(self::$queue[$position])) {
            return self::$queue[$position];
        } elseif (!empty($position) && !empty($index) && isset(self::$queue[$position][$index])) {
            return self::$queue[$position][$index];
        }
        return;
    }

/**
 * Queue function
 *
 * Add javascript to queue
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @param string  $position position to place js, (head or body), required
 * @param string  $type type of data to queue, (src or code), required
 * @param string  $data data to queue (filepath, or code fragment), required
 * @param string  $index index to use, optional
 * @return boolean true on success
 * @todo make private once xarTpl functions are deprecated
**/
    public function queue($position, $type, $data, $index = '')
    {
        if (empty($position) || empty($type) || empty($data)) {return;}

        // keep track of javascript when we're caching
        xarCache::addJavaScript($position, $type, $data, $index);
        
        if (!isset(self::$queue[$position]))
            self::$queue[$position] = array();
        
        if (empty($index)) {
            self::$queue[$position][] = array('type' => $type, 'data' => $data);
        } else {        
            self::$queue[$position][$index] = array('type' => $type, 'data' => $data);        
        }
        return true;
    }    

/**
 * Find file function
 *
 * Searches over-ride paths for specified file
 * @author Jason Judge
 * @author Chris Powis <crisp@xaraya.com>
 * @access private
 * @param  array   $args array of optional parameters<br/>
 *         string  $args[filename] name of file to find<br/> 
 *         string  $args[module] name of module to look for filename in, optional, default current module<br/>
 *         integer $args[modid] regid of module to look for filename in (deprecated, use module name)<br/>
 *         integer $args[property] name of property to look for filename in<br/>
 * @return string path to file if found, empty otherwise
 * @throws none
**/
    private function findfile($args)
    {
        extract($args);
        
        if (empty($filename) || !is_string($filename)) return '';

        // Bug 5910: If the path has GET parameters, then move them aside for now.
        if (strpos($filename, '?') !== false) {
            list($filename, $params) = explode('?', $filename, 2);
            $params = '?' . $params;
        } else {
            $params = '';
        } 

        // Initialise the search path.
        $searchPath = array();

        if (!empty($property)) {
            // This file is in a property
            // The search path for the JavaScript file.
            $searchPath[] = sys::code() . 'properties/' . $property . '/xartemplates/includes/' . $filename;
        } else {
            // This file is in a module
            // Use the current module if none supplied.
            if (empty($module) && empty($modid)) {
                list($module) = xarController::$request->getInfo();
            }
    
            // Get the module ID from the module name.
            if (empty($modid) && !empty($module)) {
                $modid = xarMod::getRegID($module);
            }
    
            // Get details for the module if we have a valid module id.
            if (!empty($modid)) {
                $modInfo = xarMod::getInfo($modid);
                // Get module directory if we have a valid module.
                if (!empty($modInfo)) {
                    $modOsDir = $modInfo['osdirectory'];
                }
            }
    
            // Theme base directory.
            $themedir = xarTplGetThemeDir();
    
            // The search path for the JavaScript file.
            $searchPath[] = $themedir . '/scripts/' . $filename;
            if (isset($modOsDir)) {
                $searchPath[] = $themedir . '/modules/' . $modOsDir . '/includes/' . $filename;
                $searchPath[] = $themedir . '/modules/' . $modOsDir . '/xarincludes/' . $filename;
                $searchPath[] = sys::code() . 'modules/' . $modOsDir . '/xartemplates/includes/' . $filename;
            }
        }

        foreach($searchPath as $filePath) {
            if (file_exists($filePath)) {break;}
            $filePath = '';
        }

        if (empty($filePath)) {
            return;
        }

        return $filePath . $params;
                       
    }
    
    // prevent cloning of singleton instance
    public function __clone()
    {
        throw new ForbiddenException();
    }
    
}
?>