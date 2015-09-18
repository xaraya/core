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
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/70.html
**/
/**
 * Base JS Class
**/

/**
 * Notes
 * This is a persistent object. Once instantiated it saves itself in a modvar
 * Each time it wakes up it checks the filesystem for JS libraries 
 * So rather than storing and accessing such information in the database,
 * we can get it by simply instantiating this object.
**/
class xarJS extends Object
{
    // the name of the module and the modvar to use for storing this object
    const STORAGE_MODULE           = 'themes';
    const STORAGE_VARIABLE         = 'js.libs';
    // base folder to look in for scripts
    const SCRIPTS_BASE             = 'scripts';
    // base folder to look in for script styles
    const SCRIPTS_STYLE            = 'style';
    // base folder to look in for libs
    const LIB_BASE                 = 'lib';
    // base folder to look in for lib styles
    const LIB_STYLE                = 'style';

    // base folder to look in for plugins
    const LIB_PLUGINS              = 'plugins';

    // the file names for plugins and lib xml files
    const LIB_XML                  = 'xarlib.xml';
    const LIB_PLUGIN_XML           = 'xarplugin.xml';

    // private properties - these are discarded when the object goes out of scope
    // this singleton instance
    private static $instance;
    // the queue of js
    public static $js;

    // public properties - these are stored when the property goes out of scope
    // array of lib objects
    public $local_libs      = array();
    // array of lib objects
    public $remote_libs     = array();
    // default libs to load...
    public $default_libs    = array();

    // array of script srcs found by scope ...
    public $scripts = array();

    // optionally cache results for $refresh seconds
    public $refresh = 0;
    // keep track of last run for caching
    public $last_run = 0;

/**
 * Magic methods to make this object persistent
**/

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
        // todo: run init scripts
        //$this->scan();
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
            $this->register($lib);
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
            xarModVars::set(xarJS::STORAGE_MODULE, xarJS::STORAGE_VARIABLE, serialize($this));
        } catch (Exception $e) {
            xarModVars::delete(xarJS::STORAGE_MODULE, xarJS::STORAGE_VARIABLE);
            xarModVars::set(xarJS::STORAGE_MODULE, xarJS::STORAGE_VARIABLE, serialize($this));
       }
    }

/**
 * Prevent cloning singleton instance
 * We only ever want there to be one instance of this object
**/
    public function __clone()
    {
        throw new ForbiddenOperationException('__clone', 'Not allowed to #(1) this singleton');
    }

/**
 * Static methods
**/

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
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            // try unserializing the stored modvar
            self::$instance = @unserialize(xarModVars::get(xarJS::STORAGE_MODULE, xarJS::STORAGE_VARIABLE));
            // fall back to new instance (first run)
            if (empty(self::$instance)) {
                $c = __CLASS__;
                // this is the one and only time the __construct() method will be run
                self::$instance = new $c;
            }
        }
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
        // we want to look in all properties
        $properties = xarMod::apiFunc('dynamicdata', 'user', 'getproptypes');

        // set default paths and filenames
        $baseDir     = xarTpl::getBaseDir();
        $themeDir    = xarTpl::getThemeDir();
        $themeName   = xarTpl::getThemeName();
        $commonDir   = xarTpl::getThemeDir('common');
        $codeDir     = sys::code();
        $libBase     = xarJS::LIB_BASE;
        $libXml      = xarJS::LIB_XML;
        $pluginXml   = xarJS::LIB_PLUGIN_XML;

        $paths = array();
        // search common too
        $themes[] = array('osdirectory' => 'common');
        // first we want to look in each active theme...
        foreach ($themes as $theme) {
            $themeOSDir = $theme['osdirectory'];
            // look for libs in themes/<theme>/lib/*
            $paths[] = "{$baseDir}/{$themeOSDir}/{$libBase}";
            // then in each active module, this theme
            foreach ($modules as $mod) {
                $modOSDir = $mod['osdirectory'];
                // look in themes/<theme>/modules/<module>/lib/*
                $paths[] = "{$baseDir}/{$themeOSDir}/modules/{$modOSDir}/{$libBase}";
            }
        }
        // now we look in each active module
        foreach ($modules as $mod) {
            $modOSDir = $mod['osdirectory'];
            // look in code/modules/<module>/xartemplates/lib/libname/*
            $paths[] = "{$codeDir}modules/{$modOSDir}/xartemplates/{$libBase}";
        }
        // now we look in each property
        foreach ($properties as $property) {
            $propdir = $property['name'];
            // look in code/properties/<property>/xarscripts/lib/libname/*
            $paths[] = "{$codeDir}properties/{$propdir}/{$libBase}";
            // look in code/properties/<property>/xartemplates/lib/libname/*
            $paths[] = "{$codeDir}properties/{$propdir}/xartemplates/{$libBase}";
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
                    $this->local_libs[$lib] = new xarJSLib($lib);
                    
                // refresh lib
                $this->local_libs[$lib]->versions = $versions;
                $this->local_libs[$lib]->findFiles();
                // Sort by version in descending order
                krsort($this->local_libs[$lib]->scripts);
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
 * Methods of this object instance
**/
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
 *         string  $args[scope] the scope in which to look for src files
 *         string  $args[code] code to include if type is code<br/>
 *         mixed   $args[filename] deprecated use $args[src] instead<br/>
 *         mixed   $args[src] array containing filename(s) or string comma delimited list<br/>
 *                 name of file(s) to include, required if type is src, or<br/>
 *                 file(s) to get contents from if type is code and code isn't supplied<br/>
 *         string  $args[lib] name of js lib to load, optional
 *         string  $args[version] version of js lib to load, optional
 *         string  $args[style] name of js lib style to load, optional
 *         string  $args[plugin] name of js lib plugin to load, optional
 *         string  $args[pluginversion] version of js lib plugin to load, optional
 *         string  $args[pluginstyle] name of js plugin style to load, optional
 *         string  $args[event] name of js lib event to attach code to
 *         string  $args[property] name of property to look for src in
 *         string  $args[module] name of module to look for file(s) in, optional, default current module<br/>
 *         string  $args[index] optional index in queue relative to other scripts<br/>
 * @return boolean true on success
 * @throws none
**/
/**
 * Catch the following xar:javascript declarations
 * load some javascript source file
 * <xar:javascript [position="head"] [type="src"] src="some.js"/>
 * embed javascript using code supplied or contents of filename supplied
 * <xar:javascript [position="head"] type="code" [src="somesrc.js"|code="somecode();"] />
 * load a lib, optionally by lib version, optionally specifying filename
 * <xar:javascript lib="libname" [version="x.y.z"] [src="somelib.js"] />
 * load a plugin, optionally by version, optionally specifying filename
 * <xar:javascript lib="libname" plugin="pluginname" [src="someplugin.js"] />
 * queue a lib event using code supplied or contents of filename supplied
 * <xar:javascript lib="libname" event="ready" [code="somecode();"|src="somesrc.js"] ../>
**/
    public function register($args)
    {
        extract($args);

        $tag = array();
        $tag['position'] = !empty($position) ? $position : 'head';
        // support deprecated use of filename parameter (for now)
        if (empty($src) && !empty($filename)) $src = $filename;

        // try to determine scope if none supplied
        if (empty($scope)) {
            // scope can be implied by attributes
            if (!empty($block)) {
                $scope = 'block';
            } elseif (!empty($module)) {
                // have module, presume module scope
                $scope = 'module';
            } elseif (!empty($property)) {
                // have property, presume property scope
                $scope = 'property';
            } else {
                $scope = 'theme';
            }
        }
        // validate scope param
        switch ($scope) {
            case 'theme':
            case 'common':
                // checkme: nothing special for these ?
                $package = null;
            break;
            case 'block':
                // catch solo block
                if (!empty($block)) {
                    $package = $block;
                    $tag['block'] = $block;
                    break;
                }
                // fall back to current block module calling the tag
                if (empty($module))
                    $module = xarVarGetCached('Security.Variables', 'currentmodule');
                // block scope falls through to module validation
            case 'module':
                // fall back to current module calling the tag
                if (empty($module))
                    $module = xarMod::getName();
                // got to have a module
                if (empty($module)) return;
                $tag['module'] = $module;
                $package = $module;
            break;
            case 'property':
                // got to have a property in property scope
                if (empty($property)) return;
                $tag['property'] = $property;
                $package = $property;
            break;
       }
       $tag['scope'] = $scope;

       // try to determine type param if none supplied...
       if (empty($type)) {
            // type can be implied by attributes
            if (!empty($plugin)) {
                $type = 'plugin';
            } elseif (!empty($event)) {
                $type = 'event';
            } elseif (!empty($lib)) {
                $type = 'lib';
            } elseif (!empty($code)) {
                $type = 'code';
            } else {
                $type = 'src';
            }
        }
        // validate type param
        switch ($type) {
            // lib plugin
            case 'plugin':
                // must have specified a plugin to load
                if (empty($plugin)) return;
                // must have specified lib
                if (empty($lib)) return;
                // @todo: check lib exists
                $tag['lib'] = $lib;
                // @todo: check plugin exists
                $tag['plugin'] = $plugin;
                // optionally specific plugin source file name
                if (empty($file)) $file = '';
                // optionally specify plugin version
                if (empty($version) || (!empty($version) && $version == 'latest')) {
                    $version = '';
                }
                // optionally specify plugin style
                if (empty($style)) $style = '';

                $info = $this->getPluginInfo($lib, $plugin, $version, $file, $style);
                if ($info['origin'] == 'local') {
                    $src = xarServer::getBaseURL() . $info['src'];
                } else {
                    $src = $info['src'];
                }
            break;
            // lib event
            case 'event':
                // must have specified an event to load
                if (empty($event)) return;
                // must have specified lib
                if (empty($lib)) return;
                // must have specified code, or filename to get code from
                if (empty($code) && empty($filename)) return;
                // @todo: check lib exists
                $tag['lib'] = $lib;
                // @todo: check lib has event
                $tag['event'] = $event;
                $tag['code'] = !empty($code) ? $code : '';
                // @todo: queue and bail here if we have code...
                if (!empty($tag['code'])) {
                    $tag['type'] = 'event';
                    return $this->queue($tag['position'], $tag['type'], $tag['scope'], $tag['code'], $tag);
                }
                if (empty($src)) return;
                $tag['base'] = !empty($base) ? $base : xarJS::SCRIPTS_BASE;
                $tag['src'] = $src;
            break;
            // lib
            case 'lib':
                // must have specified lib
                if (empty($lib)) return;
                // @todo: check lib exists...
                $tag['lib'] = $lib;
                // optionally specify lib source file name
                if (empty($src)) $src = '';
                // optionally specify lib version
                if (empty($version) || (!empty($version) && $version == 'latest')) {
                    $version = '';
                }
                $info = $this->getLibInfo($lib, $version, $src);
                if (empty($info)) return;
                $tag['version'] = $info['version'];
                $tag['base'] = $info['base'];
                $package = $info['package'];
                $src = $info['src'];

                // optionally specify lib style
                if (!empty($style))
                    $tag['style'] = $style;

                // If this is a local library, it may include plugins: register them
                if ($info['origin'] == 'local') {
                    $hasplugins = false;
                    $plugins = array();
                    if (!empty($args['plugins'])) {
                        if ($args['plugins'] == 'all') {
                            $hasplugins = true;
                            $args['plugins'] = array();
                        } else {
                            $requestedplugins = explode(',',$args['plugins']);
                            foreach ($requestedplugins as $plugin) {
                                $namearray = explode(':',$plugin);
                                $plugins[trim($namearray[0])] = trim($plugin);
                            }
                        }
                    }
                    if (!empty($plugins)) $hasplugins = true;
                
                    if ($hasplugins) {
                        sys::import('xaraya.version');
                        $libfilePath = $this->findFile($scope, trim($src), $tag['base'], $package);
                        foreach ($this->local_libs[$info['lib']]->plugins as $name => $plugin) {
                            $libname = "/".$lib."/";
                            $pos = strripos ($libfilePath ,$libname);
                            if (!empty($plugins)) {
                                // One or more plugin names were passed
                                // If we don't have such a plugin, bail
                                if (!isset($plugins[$name])) continue;
                                
                                $namearray = explode(':',$plugins[$name]);
                                // Get (perhaps) the version and file name and style passed
                                $version = !empty($namearray[1]) ? trim($namearray[1]) : '';
                                $file = !empty($namearray[2]) ? trim($namearray[2]) : $name;
                                $style = !empty($namearray[3]) ? trim($namearray[3]) : '';
                            } else {
                                // No specific plugins passed, just "all"
                                // In this case we assume the name of the file to be loaded will be the same as the plugin name + ".js"
                                $version = '';
                                $file = $name;
                            }
                            $registerargs = array(
                                            'plugin' => $name,
                                            'lib' => $info['lib'],
                                            );
                            if (!empty($version)) $registerargs['version'] = $version;
                            if (!empty($file)) $registerargs['file'] = $file;
                            if (!empty($style)) $registerargs['style'] = $style;
                            $this->register($registerargs);
                        }
                    }
                }
            break;
            // code
            case 'code':
                // must have specified code, or filename to get code from
                if (empty($code) && empty($src)) return;
                $tag['code'] = !empty($code) ? $code : '';
                if (!empty($tag['code'])) {
                    $tag['type'] = 'code';
                    return $this->queue($tag['position'], $tag['type'], $tag['scope'], $tag['code'], $tag);
                }
                // if code wasn't supplied fall through to src validation
            // src
            case 'src':
                // must have specified a filename as source
                if (empty($src)) return;
                $tag['src'] = $src;
                // optional base folder (default scripts)
                $tag['base'] = !empty($base) ? $base : xarJS::SCRIPTS_BASE;
            break;
        }
        $tag['type'] = $type;

        // from here on we have source(s) to fetch
        $files = !is_array($src) ? explode(',', $src) : $src;

        foreach ($files as $file) {
            // check if file is local...
            $server = xarServer::getHost();
            if ($tag['type'] == "plugin") {
                // Whatever the URL, just include it
                $tag['url'] = $file;                
            
            } elseif ($tag['type'] == "src" &&
                preg_match("!://($server|localhost|127\.0\.0\.1)(:\d+|)/!",$file)) {
                // Local absolute URL, just include it
                $tag['url'] = $file;                
            
            } elseif (!preg_match("!^https?://!",$file) ||
                preg_match("!://($server|localhost|127\.0\.0\.1)(:\d+|)/!",$file)) {
                // break off any params
                if (strpos($file, '?') !== false)
                    list($file, $params) = explode('?', $file, 2);
                // get path relative to web root
                $relPath = $this->findFile($scope, trim($file), $tag['base'], $package);
                if (empty($relPath)) continue;
                // if type is code or event, we want the file contents
                if ($type == 'code' || $type == 'event') {
                    $code = @file_get_contents($relPath);
                    if (empty($code)) continue;
                    $tag['code'] = $code;
                }
                // fill in the other tag parameters
                $tag['src'] = $file;

                // Turn relative path into an absolute URL
                $webDir = sys::web();
                if (!empty($webDir) && strpos($relPath, $webDir) === 0) {
                    $relPath = substr($relPath, strlen($webDir));
                }
                $filePath = xarServer::getBaseURL() . $relPath;

                if (!empty($params)) {
                    $filePath .= '?'.$params;
                    unset($params);
                }
                $tag['url'] = $filePath;
            
            } elseif ($type=='src' || $type=='lib' || $type=='plugin') {
                // Not a local file, just include the external source
                $tag['src'] = $file;
                $tag['url'] = $file;
            } else {
                continue;
            }
            // queue the tag
            return $this->queue($tag['position'], $type, $scope, $tag['url'], $tag);
        }

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
    public function queue($position, $type, $scope, $data, $tag, $index='')
    {
        //if (empty($scope) || empty($position) || empty($type) || empty($data) || empty($tag)) return;
        if (empty($position) || empty($type) || empty($scope) || empty($data) || empty($tag)) return;

        // keep track of javascript when we're caching
        xarCache::addJavascript($tag);

        // init the queue
        if (!isset(self::$js)) {
            // scope rendering order
            $scopes = array(
                'theme' => array(),
                'module' => array(),
                'block' => array(),
                'property' => array(),
            );
            // type rendering order
            $types = array(
                'lib' => $scopes,
                'plugin' => $scopes,
                'src' => $scopes,
                'code' => $scopes,
                'event' => $scopes,
            );

            // positions
            self::$js = array(
                'head' => $types,
                'body' => $types,
            );
            unset($scopes); unset($types);
        }
        // skip unknown position/type/scope (for now)
        if (!isset(self::$js[$position][$type][$scope])) return;

        if (empty($index))
            $index = md5($data);

        self::$js[$position][$type][$scope][$index] = $tag;
        return true;
    }

/**
 * Get Queued function
 *
 * Get queued JS, optionally by position, type, scope
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
    public function findFile($scope, $file, $base, $package='')
    {
        if (empty($scope) || empty($file) || empty($base)) return;

        // set common paths to look in
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
            case 'block':
                // Standalone blocks
                if (empty($package)) return;
                $paths[] = "$themeDir/blocks/$package/$base/$file";
                $paths[] = "$commonDir/blocks/$package/$base/$file";
                $paths[] = "{$codeDir}blocks/$package/xartemplates/$base/$file";
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
                $paths[] = $themeDir . '/modules/' . $modOsDir . '/includes/' . $file;
                // themes/theme/modules/module/xarincludes (legacy)
                $paths[] = $themeDir . '/modules/' . $modOsDir . '/xarincludes/' . $file;
                // themes/common/modules/module/scripts
                $paths[] = $commonDir . '/modules/' . $modOsDir . '/' . $base . '/' . $file;
                // code/modules/module/xartemplates/scripts
                $paths[] = $codeDir . 'modules/' . $modOsDir . '/xartemplates/' . $base . '/' . $file;
                // code/modules/module/xartemplates/includes (legacy)
                $paths[] = $codeDir . 'modules/' . $modOsDir . '/xartemplates/includes/' . $file;
                break;
            case 'property':
                // Standalone properties
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
         
         // Debug display
         if (xarModVars::get('themes','debugmode') && 
         in_array(xarUser::getVar('id'),xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
            foreach ($paths as $path) {
                echo xarML('Possible location: ') . $path . "<br/>";                
            }
         }

        // We are looking for a specific version of a file/library
         foreach ($paths as $path) {
             if (!file_exists($path)) continue;
             $filePath = $path;
            // Debug display
             if (xarModVars::get('themes','debugmode') && 
             in_array(xarUser::getVar('id'),xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
                echo xarML('Chosen: ') . $path . "<br/>";
             }
             break;
         }
         if (empty($filePath)) return;

         return $filePath;
    }

/**
 * getLibInfo method
 *
 * Get the information of a specific version of a library that Xaraya is aware of
 * This can be a local or remote library
 * Fallback is to the latest version that Xaraya knows about
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 * @param string $lib JS library name
 * @param string $vers JS library version
 * @param string $src JS library source
 * @return array library information
 * @throws none
**/
    private function getLibInfo($lib, $vers='', $src='')
    {
        // We will look at the local and remote libraries Xaraya is aware of
        // Ceteris paribus, remote libraries will be privileged
        
        $candidates = array();

        if (isset($this->local_libs[$lib])) {
            $thislib = $this->local_libs[$lib];
        
            // Start by taking the latest version
            $scripts = $thislib->scripts;
            foreach ($scripts as $version => $versionarray) {
                foreach ($versionarray as $scope => $scopearray) {
                    foreach ($scopearray as $package => $packagearray) {
                        foreach ($packagearray as $dir => $dirarray) {
                            foreach ($dirarray as $file => $filearray) {
                                if (!empty($src) && ($filearray['src'] != $src)) continue;
                                if (!empty($vers) && ($filearray['version'] != $vers)) continue;
                                $candidates[] = $filearray;
                            }
                        }
                    }
                }
            }
        }

        foreach ($this->remote_libs as $thislib) {
            if ($thislib['type'] != 'lib') continue;
            if (!empty($lib) && ($thislib['lib'] != $lib)) continue;
            if (!empty($src) && ($thislib['src'] != $src)) continue;
            if (!empty($vers) && ($thislib['version'] != $vers)) continue;
            $candidates[] = $thislib;
        }
        
        // Sort the libraries by descending version and origin
        if (!empty($candidates)) {
            foreach ($candidates as $key => $row) {
                $tempversion[$key] = $row['version'];
                $temporigin[$key]  = $row['origin'];
            }
            array_multisort($tempversion, SORT_DESC, $temporigin, SORT_DESC, $candidates);
        }
        // Return the first acceptable candidate
        $chosen = current($candidates);
        return $chosen;
    }

/**
 * getPluginInfo method
 *
 * Get the information of a specific version of a plugin that Xaraya is aware of
 * This can be a local or remote library
 * Fallback is to the latest version that Xaraya knows about
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 * @param string $lib JS library name
 * @param string $vers JS library version
 * @param string $src JS library source
 * @return array library information
 * @throws none
**/
    private function getPluginInfo($lib, $plug, $vers='', $file='', $style='')
    {
        // We will look at the local and remote library plugins Xaraya is aware of
        // Ceteris paribus, remote plugins will be privileged
        
        $candidates = array();

        if (isset($this->local_libs[$lib])) {
            $thislib = $this->local_libs[$lib];

            // Start by taking the latest version, or the version asked for
            $plugins = $thislib->plugins;
            foreach ($plugins as $plugin => $pluginarray) {
                if ($plug != $plugin)  continue;
                foreach ($pluginarray as $version => $versionarray) {
                    if (!empty($vers) && ($version != $vers)) continue;
                    //if (!empty($src) && ($versionarray['src'] != $src)) continue;
                    foreach ($versionarray as $filearray) {
                        $pluginfile = basename($filearray['src'], '.js');
                        if (!empty($file) && ($pluginfile != $file)) continue;
                        if (!empty($vers) && ($filearray['version'] != $vers)) continue;
                        $candidates[] = $filearray;
                    }
                }
            }
        }

        foreach ($this->remote_libs as $thislib) {
            if ($thislib['type'] != 'plugin') continue;
            if (!empty($lib) && ($thislib['parent'] != $lib)) continue;
            if (!empty($vers) && ($thislib['version'] != $vers)) continue;
            $pluginfile = basename($thislib['src'], '.js');
            if (!empty($file) && ($pluginfile != $file)) continue;
            $candidates[] = $thislib;
        }
        
        // Sort the libraries by descending version and origin
        if (!empty($candidates)) {
            foreach ($candidates as $key => $row) {
                $tempversion[$key] = $row['version'];
                $temporigin[$key]  = $row['origin'];
           }
            array_multisort($tempversion, SORT_DESC, $temporigin, SORT_DESC, $candidates);
        }
        // Return the first acceptable candidate
        $chosen = current($candidates);
        return $chosen;
    }
}

/**
 * Base JS Lib Class
 *
 * This object models a JS Library
**/
class xarJSLib extends Object
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
    public $scripts       = array(); // all scripts
    public $styles        = array(); // all styles
    public $plugins       = array(); // all plugins
    public $templates     = array(); // all templates

    public function __construct($name)
    {
        if (empty($name))
            throw new BadParameterException($name, 'Invalid name "#(1)" for xarJSLib');
        // first run, populate the library meta data
        $this->name = $name;
        $this->displayname = ucfirst($this->name);
        $this->description = xarML('#(1) JS Library', $this->displayname);
        $this->osdirectory = xarVarPrepForOS($this->name);
    }
/**
 * Rebuild the entire cache of meta data for this lib
 *
**/
    private function rebuild()
    {


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
        // we want to look in all properties
        $properties = xarMod::apiFunc('dynamicdata', 'user', 'getproptypes');

        // set default paths and filenames
        $libName     = $this->name;
        $baseDir     = xarTpl::getBaseDir();
        $themeDir    = xarTpl::getThemeDir();
        $themeName   = xarTpl::getThemeName();
        $commonDir   = xarTpl::getThemeDir('common');
        $codeDir     = sys::code();
        $libBase     = xarJS::LIB_BASE;
        $libXml      = xarJS::LIB_XML;
        $pluginXml   = xarJS::LIB_PLUGIN_XML;

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
                $paths['module'][$modOSDir] = "{$baseDir}/{$themeOSDir}/modules/{$modOSDir}/{$libBase}/{$libName}";
            }
        }
        // now we look in each active module
        foreach ($modules as $mod) {
            $modOSDir = $mod['osdirectory'];
            // look in code/modules/<module>/xartemplates/lib/libname/*
            $paths['module'][$modOSDir] = "{$codeDir}modules/{$modOSDir}/xartemplates/{$libBase}/{$libName}";
        }
        // now we look in each property
        foreach ($properties as $property) {
            $propdir = $property['name'];
            // look in code/properties/<property>/xarscripts/lib/libname/*
            $paths['properties'][$propdir] = "{$codeDir}properties/{$propdir}/{$libBase}/{$libName}";
            // look in code/properties/<property>/xartemplates/lib/libname/*
//            $paths['properties'][$propdir] = "{$codeDir}properties/{$propdir}/xartemplates/{$libBase}/{$libName}";
        }
        
        // Load the version class to check versions
        sys::import('xaraya.version');
        
        // find files in all lib folders, all themes, all modules, all properties
        $this->scripts = array();
        $this->plugins = array();
        foreach ($paths as $scope => $packages) {
            foreach ($packages as $package => $path) {
                if (!is_dir($path)) continue;
                $versions = xarJS::getFolders($path, 1);
                if (empty($versions)) continue;
                foreach (array_keys($versions) as $version) {
                    // Check if this is a valid version folder
                    $valid = xarVersion::parse($version);
                    if(!$valid) continue;
                    
                    $subpath = $path . "/" . $version;
                    $files = xarJS::getFiles($subpath);
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
                                break;
                                case 'css':
                                    $tag['file'] = $file;
                                    $this->styles[$version][$scope][$package][$base][$file] = $tag;
                                break;
                                case 'xt':
                                    $tag['template'] = str_replace('.xt', '', $file);
                                    $this->templates[$version][$scope][$package][$base][$file] = $tag;
                                break;
                                case 'xml':
                                    if ($file == xarJS::LIB_XML) {
                                        // read the lib xml file...
                                        $this->readLibXml($filepath);
                                        //$this->xml[$scope][$package][$base][$file] = $tag;
                                    } elseif ($file == xarJS::LIB_PLUGIN_XML) {

                                    }
                                break;
                            }
                        }
                    }
                }
                // Check for plugins
                $subpath = $path . "/" . $this->pluginfolder;
                if (!is_dir($subpath)) continue;
                $plugins = xarJS::getFolders($subpath, 1);
                if (empty($plugins)) continue;
                foreach (array_keys($plugins) as $plugin) {
                    // We have a plugin folder
                    $this->plugins[$plugin] = array();
                    $pluginpath = $subpath . "/" . $plugin;
                    $versions = xarJS::getFolders($pluginpath, 1);
                    if (empty($versions)) continue;
                    foreach (array_keys($versions) as $version) {
                        // Check if this is a valid version folder
                        $valid = xarVersion::parse($version);
                        if(!$valid) continue;
                        $versionpath = $pluginpath . "/" . $version;
                        $this->plugins[$plugin][$version] = array();

                        // Get the plugin files for this version
                        $files = xarJS::getFiles($versionpath);
                        if (empty($files)) continue;
                        foreach ($files as $folder => $items) {
                            foreach ($items as $file => $filepath) {
                                $ext = pathinfo($file, PATHINFO_EXTENSION);
                                switch ($ext) {
                                    case 'js':
                                        $this->plugins[$plugin][$version][$file] = array(
                                                                                'src' => $filepath,
                                                                                'version' => $version,
                                                                                'origin' => 'local',
                                                                                );
                                    break;
                                    case 'css':
                                    case 'xt':
                                    case 'xml':
                                    // Do nothing for now
                                }
                            }
                        }
                    }
                }
            }
        }
    }

/**
 * Read Lib XML
 *
 * Populate lib meta data from xml file
**/
    private function readLibXml($xmlPath)
    {
        if (!$xml = $this->loadXml($xmlPath)) return;
        if (!empty($xml->name))
            $this->name = (string) $xml->name;
        if (!empty($xml->displayname))
            $this->displayname = (string) $xml->displayname;
        if (!empty($xml->description))
            $this->description = (string) $xml->description;
    }

/**
 * Read Lib Plugin XML
 *
 * Populate lib plugin meta data from xml file
**/
    private function readPluginXml($xmlPath)
    {
        $meta = array();
        if (!$xml = $this->loadXml($xmlPath)) return;
    }
/**
 * Private xml loader
**/
    private function loadXml($xmlPath)
    {
        if (!file_exists($xmlPath)) return;
        $string = @file_get_contents($xmlPath);
        if (!$string || !function_exists('simplexml_load_string')) return;
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($string);
        if (!$xml) return;
        return $xml;
    }

    public function getInfo()
    {
        return $this->getPublicProperties();
    }

    private function init()
    {

    }

}

?>