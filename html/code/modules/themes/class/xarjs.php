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
    const JSCOMMON = 'common';
    
    private static $instance;
    private static $queue;
    private static $js;

    // prevent direct creation of this object
    private function __construct()
    {
        // @todo: evaluate the need for these here, possibly move to register method ?
        // set common filepaths 
        $this->themesDir = xarConfigVars::get(null, 'Site.BL.ThemesDirectory');
        $this->themeDir = xarTplGetThemeDir();
        $commonDir = xarModVars::get('themes', 'themes.common');
        if (empty($commonDir)) $commonDir = xarJS::JSCOMMON;
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
 *         string  $args[position] position to render the js, eg head or body, optional, default head<br/>
 *         string  $args[index] optional index in queue relative to other scripts<br/>
 * @return boolean true on success
 * @throws none
**/
    public function register($args)
    {
        extract($args);        

        if (empty($position)) 
            $position = 'head';
        if (empty($type)) 
            $type = 'src';
        
        // init the script tag
        $script = array(
            'position' => $position,
            'type' => $type,
            'framework' => '',
            'module' => !empty($module) ? $module : '',
            'filename' => '',
            'code' => !empty($code) ? $code : '',
            'url' => '',        
        );                 
        
        // handle js types
        switch ($type) {
            case 'code':
                // if we have code, we're done
                if (!empty($script['code'])) {
                    return $this->queue($position, $type, $script, $index);
                }
                // fall through to look for file 
            case 'src':
                if (empty($module))
                    $module = xarMod::getName();           
                break;

            case 'framework':
                if (empty($framework)) 
                    // todo.. get default framework
                    $framework = xarModVars::get('themes', 'js.framework');
                break;
            case 'plugin':
                if (empty($framework)) 
                    // todo.. get default framework
                    $framework = xarModVars::get('themes', 'js.framework');                
                break;
            case 'event':
                if (empty($framework)) 
                    // todo.. get default framework
                    $framework = xarModVars::get('themes', 'js.framework');            
                break;
        }
        
        // if we're here, we have files to look for
        $files = !is_array($filename) ? explode(',', $filename) : $filename;

        foreach ($files as $file) {
            $filePath = $this->findfile($file, $module);
            if (!empty($filePath)) {
                $script['filename'] = $file;
                if ($script['type'] == 'code' || $script['type'] == 'event') {
                    $code = file_get_contents($filePath);
                    if (empty($code)) continue;
                    $script['code'] = $code;
                } else {
                    $script['url'] = "{$this->baseUrl}{$filePath}";
                }
                $this->queue($position, $type, $script, $index);
            }
            
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
 *        string  $args[type] type to get JS for, optional
 * @return mixed array of queued js, false if none found
 * @throws none
**/
    public function getQueued($args)
    {
        extract($args);        
        if (empty($position) && empty($type)) {
            return self::$js;
        } elseif (!empty($position) && empty($type) && isset(self::$js[$position])) {
            return array($position => self::$js[$position]);
        } elseif (!empty($position) && !empty($type) && isset(self::$js[$position][$type])) {
            return array($position => array($type => self::$js[$position][$type]));
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
 * @param string  $position position to place js, [(head)|body], required
 * @param string  $type type of data to queue, [framework|plugin|event|(src)|code], required
 * @param string  $framework name of framework
 * @param string  $data data to queue (filepath, or code fragment), required
 * @param string  $index index to use, optional
 * @return boolean true on success
 * @todo make private once xarTpl functions are deprecated
**/
    public function queue($position, $type, $data, $index='')
    {
        if (empty($position) || empty($type) || empty($data)) {return;}

        // keep track of javascript when we're caching
        // @checkme: <chris/> why is this? we cache them already in the static $js var
        // xarCache::addJavaScript($position, $type, $data, $index);
      
        // init the queue
        if (!isset(self::$js)) {
            $headtypes = array('framework' => array(), 'plugin' => array(), 'src' => array(), 'code' => array(), 'event' => array());
            $bodytypes = array('src' => array(), 'code' => array());
            self::$js = array(
                'head' => $headtypes,
                'body' => $bodytypes,
            );
            unset($headtypes); unset($bodytypes);
        }
        // skip unknown positions/scopes/types (for now)
        if (!isset(self::$js[$position][$type])) return;
        
        if (empty($index))
            // set unique index so file is only included once
            $index = md5(serialize($data));
        
        self::$js[$position][$type][$index] = $data;
        
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
 *         string  $args[module] name of module to look for filename in, optional, default none
 * @return string path to file if found, empty otherwise
 * @throws none
**/
    private function findfile($filename, $module=null)
    {
        if (empty($filename) || !is_string($filename)) return;

        // Bug 5910: If the path has GET parameters, then move them aside for now.
        if (strpos($filename, '?') !== false) {
            list($filename, $params) = explode('?', $filename, 2);
            $params = '?' . $params;
        } else {
            $params = '';
        }
        
        $searchPath = array();
        // look in current theme scripts
        $searchPath[] = "{$this->themeDir}/scripts/{$filename}";
        if (!empty($module)) {
            // look in current theme module scripts
            $searchPath[] = "{$this->themeDir}/modules/{$module}/scripts/{$filename}";
            $searchPath[] = "{$this->themeDir}/modules/{$module}/includes/{$filename}";
            $searchPath[] = "{$this->themeDir}/modules/{$module}/xarincludes/{$filename}";
        }     
        // look in common scripts
        $searchPath[] = "{$this->commonDir}scripts/{$filename}";
        if (!empty($module)) {
            // look in common module scripts
            $searchPath[] = "{$this->commonDir}/modules/{$module}/scripts/{$filename}";
            $searchPath[] = "{$this->commonDir}/modules/{$module}/includes/{$filename}";
            $searchPath[] = "{$this->commonDir}/modules/{$module}/xarincludes/{$filename}";
            // look in module scripts
            $searchPath[] = "{$this->codeDir}/modules/{$module}/xarscripts/{$filename}";
            $searchPath[] = "{$this->codeDir}/modules/{$module}/xartemplates/includes/{$filename}";
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