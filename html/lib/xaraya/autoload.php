<?php
/**
 * Xaraya Autoload
 *
 * @package core
 * @subpackage autoload
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub
 */

// CHECKME: see also ini_set('unserialize_callback_func', ...) ?

/**
 * Convenience class for managing autoload functions and methods
 *
 * @todo let modules register functions and class methods during activation ?
 * @todo save the list of registered functions and class methods somewhere ?
 * @todo re-use the list of saved functions and class methods in initialize() ?
**/
class xarAutoload extends xarObject
{
    private static $registerlist = array();
    private static $classpathlist = array();
    public static $shutdown = false;

    /**
     * Initialize the list of autoload functions
     *
     * @param registerlist array list of functions and classname::methods to be registered
     * @param extensions string comma-separated list of file extensions to be checked (instead of the default ones)
     * @return none
    **/
    public static function initialize($registerlist = array(), $extensions = '')
    {
        // CHECKME: always start from scratch ?
        spl_autoload_register(null);

        // specify extensions (if not default)
        if (!empty($extensions)) {
            spl_autoload_extensions($extensions);
        }

        // add the __autoload function
        if (function_exists('__autoload')) {
            spl_autoload_register('__autoload');
        }

        // add any other specified functions and class methods
        if (!empty($registerlist)) {
            foreach ($registerlist as $function) {
                if (strpos($function,'::')) {
                    list($classname, $method) = explode('::', $function);
                    self::registerClassMethod($classname, $method);
                } else {
                    self::registerFunction($function);
                }
            }
        }

        // Load the autoload method for the core
        self::registerClassMethod('xarAutoload', 'core_autoload');

        // Load autoload functions for the modules
        if (method_exists('xarMod', 'apiFunc')) {
            $activeMods = xarMod::apiFunc('modules','admin','getlist', array('filter' => array('State' => xarMod::STATE_ACTIVE)));
        } else {
            return array();
        }
        assert(!empty($activeMods)); // this should never happen

        $loaded = array();
        foreach($activeMods as $modInfo) {
            // Get the autoload functions for the module classes, all of them except the dataproperties
            // Check that we have a valid file
            $filepath = sys::code() . 'modules/' . $modInfo['osdirectory'] . '/class/autoload.php';
            // If not, move on to the next module
            if (is_file($filepath)) {
                // Load this valid file; this automatically registers the autoload function for the module's classes
                sys::import('modules.' . $modInfo['osdirectory'] . '.class.autoload');
                $loaded[] = $modInfo['osdirectory'] . '.class.autoload';
            }
            
            // Get the autoload functions for the module's dataproperties
            // Check that we have a valid file
            $filepath = sys::code() . 'modules/' . $modInfo['osdirectory'] . '/xarproperties/autoload.php';
            // If not, move on to the next module
            if (is_file($filepath)) {
                // Load this valid file; this automatically registers the autoload function for the module's properties
                sys::import('modules.' . $modInfo['osdirectory'] . '.xarproperties.autoload');
                $loaded[] = $modInfo['osdirectory'] . '.xarproperties.autoload';
            }
        }

        // Load autoload functions for standalone properties
        sys::import('xaraya.structures.relativedirectoryiterator');
        $propertiesdir = sys::code() . 'properties/';
        if (!file_exists($propertiesdir)) throw new DirectoryNotFoundException($propertiesdir);

        $filepath = $propertiesdir . '/autoload.php';
        // Check that we have a valid file
        if (is_file($filepath)) {
            // Load this valid file; this automatically registers the autoload function for the standalone properties
            sys::import('properties.autoload');
            $loaded[] = 'properties.autoload';
        }

        $dir = new RelativeDirectoryIterator($propertiesdir);
        $files = array();
        for ($dir->rewind();$dir->valid();$dir->next()) {
            if ($dir->isDot()) continue; // temp for emacs insanity and skip hidden files while we're at it
            if (!$dir->isDir()) continue; // only dirs

            // Check this property for a tags directory
            $file = $dir->getPathName();
            // Check that we have a valid file
            $filepath = $file . '/autoload.php';
            // If not, move on to the next property
            if (!is_file($filepath)) continue;
            // Load this valid file
            $directory = basename($file);
            sys::import('properties.' . $directory . '.autoload');
            $loaded[] = $directory;
        }
        
/*
        // Load autoload functions for module properties
        // add all known property classes we might be looking for
        sys::import('modules.dynamicdata.class.properties.registration');
        $proptypes = PropertyRegistration::Retrieve();
        foreach ($proptypes as $proptype) {
            $name = strtolower($proptype['class']);
            // add sys::code() here to get the full path for module properties
            $classpathlist[$name] = sys::code() . $proptype['filepath'];
        }
*/

        return true;
    }

    /**
     * TODO: Save the list of registered autoload() functions somewhere
     *
     * @return none
    **/
    public static function saveList()
    {
        self::refreshList();
        $list = array_keys(self::$registerlist);
        // ...
    }

    /**
     * Refresh the internal list based on the actual registered autoload() functions
     *
     * @return none
    **/
    private static function refreshList()
    {
        $list = spl_autoload_functions();
        self::$registerlist = array();
        foreach ($list as $function) {
            if (is_array($function)) {
                $classname = $function[0];
                $method = $function[1];
                if (gettype($classname) === "string") {
                    self::$registerlist[$classname.'::'.$method] = 1;
                } else {
                    //self::$registerlist[$classname.'::'.$method] = 1;
                    xarLog::message("xarAutoload:refreshList: failed registering class " . get_class($classname) . " method $method", xarLog::LEVEL_DEBUG);
                }
            } else {
                if (gettype($function) === "string") {
                    self::$registerlist[$function] = 1;
                } else {
                    //self::$registerlist[$function] = 1;
                    xarLog::message("xarAutoload:refreshList: failed registering function " . get_class($function), xarLog::LEVEL_DEBUG);
                }
            }
        }
    }

    /**
     * Register a new function as __autoload()
     *
     * @param function string the name of the function to be registered
     * @return none
    **/
    public static function registerFunction($function)
    {
        spl_autoload_register($function);
        self::refreshList();
    }

    /**
     * Register a new class method as __autoload()
     *
     * @param classname string the name of the class
     * @param method string the name of the method to be registered
     * @return none
    **/
    public static function registerClassMethod($classname, $method)
    {
        spl_autoload_register(array($classname, $method));
        self::refreshList();
    }

    public static function unregisterFunction($function)
    {
        spl_autoload_unregister($function);
        self::refreshList();
    }

    public static function unregisterClassMethod($classname, $method)
    {
        spl_autoload_unregister(array($classname, $method));
        self::refreshList();
    }

    /**
     * Temporary autoload method for big Categories, DD, Privileges, Roles etc. objects
     * that might be serialized and cached - TODO: specify this at module activation ?
     */
    public static function core_autoload($class)
    {
        if (self::$shutdown) {
            return false;
        }

        $class = strtolower($class);

        // Event classes are loaded in the core
        
        $class_array = array(
            // Structures directory
            'query'                     => 'xaraya.structures.query',
            'tree'                      => 'xaraya.structures.tree',
            'xardatetime'               => 'xaraya.structures.datetime',
            'objectdescriptor'          => 'xaraya.structures.descriptor',
            'relativedirectoryiterator' => 'xaraya.structures.relativedirectoryiterator',
            'xarvariableobject'         => 'xaraya.structures.variableobject',

            'basicblock'             => 'xaraya.structures.containers.blocks.basicblock',
            'iblock'                 => 'xaraya.structures.containers.blocks.basicblock',
            'iblockgroup'            => 'xaraya.structures.containers.blocks.basicblock',
            'iblockmodify'           => 'xaraya.structures.containers.blocks.basicblock',
            'iblockdelete'           => 'xaraya.structures.containers.blocks.basicblock',
            'blocktype'              => 'xaraya.structures.containers.blocks.blocktype',
            'iblocktype'             => 'xaraya.structures.containers.blocks.blocktype',
            'menublock'              => 'xaraya.structures.containers.blocks.menublock',
            
            'deque'                  => 'xaraya.structures.sequences.deque',
            'isequence'              => 'xaraya.structures.sequences.interfaces',
            'iadapter'               => 'xaraya.structures.sequences.interfaces',
            'isequenceadapter'       => 'xaraya.structures.sequences.interfaces',
            'iqueue'                 => 'xaraya.structures.sequence.interfaces',
            'istack'                 => 'xaraya.structures.sequences.interfaces',
            'ideque'                 => 'xaraya.structures.sequences.interfaces',
            'queue'                  => 'xaraya.structures.sequences.queue',
            'sequence'               => 'xaraya.structures.sequences.sequence',
            'stack'                  => 'xaraya.structures.sequences.stack',
            'arraysequence'          => 'xaraya.structures.sequences.adapters.array_sequence',
            'dynamicdatasequence'    => 'xaraya.structures.sequences.adapters.dd_sequence',
            'sequenceadapter'        => 'xaraya.structures.sequences.adapters.sequence_adapter',

            'basiccollection'        => 'xaraya.structures.sets.collection',
            'basicset'               => 'xaraya.structures.sets.collection',
            'collection'             => 'xaraya.structures.sets.collection',
            'iset'                   => 'xaraya.structures.sets.interfaces',

            // Streams directory
            'variablestream'         => 'xaraya.streams.variables',
            
            // Version classes
            'badversionexception'    => 'xaraya.version',
            'xarversion'             => 'xaraya.version',

            // MLSBackends directory
            'xarmls__phptranslationsbackend'        => 'xaraya.mlsbackends.php',
            'itranslationsbackend'                  => 'xaraya.mlsbackends.reference',
            'xarmls__referencesbackend'             => 'xaraya.mlsbackends.reference',
            'xarmls__xmltranslationsbackend'        => 'xaraya.mlsbackends.xml',
            'xarmls__xml2phptranslationsbackend'    => 'xaraya.mlsbackends.xml2php',
            'phpbackendgenerator'                   => 'xaraya.mlsbackends.xml2php',
        );
    
        // Define the database classes
        if (xarSystemVars::get(sys::CONFIG, 'DB.Middleware') == 'PDO') {
            $database_class_array = array(

                'xardb'              => 'xaraya.pdo',
                'xarpdo'             => 'xaraya.pdo',
                'xarpdostatement'    => 'xaraya.pdo',
                'databaseinfo'       => 'xaraya.pdo',
                'pdotable'           => 'xaraya.pdo',
                'pdocolumn'          => 'xaraya.pdo',
                'resultset'          => 'xaraya.pdo',
            );
        } else {
            $database_class_array = array(
                'xardb'              => 'xaraya.creole',
            );
        }

        // Add the database classes to the class array
        $class_array = array_merge($class_array, $database_class_array);
        
        if (isset($class_array[$class])) {
            sys::import($class_array[$class]);
            return true;
        }

        return false;


        if (empty(self::$classpathlist)) {
            // add some more typical classes we might be looking for
            // ...
            // add sys::code() here to get the full path for module classes
            // self::$classpathlist[$name] = sys::code() . $filepath;
            // ...
        }

        if (isset(self::$classpathlist[$class]) && file_exists(self::$classpathlist[$class])) {
            include_once(self::$classpathlist[$class]);
            return;
        }

        return false;
    }
}
