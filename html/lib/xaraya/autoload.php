<?php
/**
 * Xaraya Autoload
 *
 * @package core
 * @subpackage autoload
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
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
class xarAutoload extends Object
{
    private static $registerlist = array();
    private static $classpathlist = array();

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
        spl_autoload_register(null, false);

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

        // TODO: work with manual specification until we get something better
        sys::import('modules.dynamicdata.class.autoload');

        // TODO: work with temporary autoload function until we get something better
        self::registerClassMethod('xarAutoload', 'autoload_todo');
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
                self::$registerlist[$classname.'::'.$method] = 1;
            } else {
                self::$registerlist[$function] = 1;
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
    public static function autoload_todo($class)
    {
        $class = strtolower($class);

        // Some predefined classes
        switch ($class)
        {
            case 'categories':
                sys::import('modules.categories.class.categories');
                return;

            // DD has been moved to its own autoload because it has lots :-)

            case 'xarmasks':
                sys::import('modules.privileges.class.masks');
                return;
            case 'xarprivileges':
                sys::import('modules.privileges.class.privileges');
                return;

            case 'role':
                sys::import('modules.roles.class.role');
                return;
            case 'rolelist':
                sys::import('modules.roles.class.role');
                return;
            case 'xarroles':
                sys::import('modules.roles.class.roles');
                return;
        }

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
?>