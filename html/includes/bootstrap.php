<?php
/**
 * Bootstrap file
 *
 * This file is the only one (longer term) which get always included when
 * running Xaraya. Everything else is lazy loaded. This file contains the
 * things which *absolutely* need to be available, which should be very little.
 * 
 * So far:
 *  - Declaration of the root Object class of which all other classes are derived
 *  - Definition of the sys class which contains methods to steer php in the right direction
 *    for getting the right files (now: inclusion and the var path)
 *
 * If anything, make absolutely sure you get the fastest implementation
 * of what you want to do here.
 *
 * @package core
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Jonn Beames
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <mrb@hsdev.com>
**/

/**
 * The Object class from which all other classes are derived.
 *
 * This is basically a placeholder extending from stdClass so we have a 
 * place to put things in our root class. There are severe limitations to what
 * can and can not be placed into this class. For example, it can not have a 
 * constructor because it would prevent descendents to have a private 
 * constructor, which is rather common in the SingleTon pattern.
 *
 * @package core
 * @todo    we would like some std. methods in here but i have no clue how to 
 *          do that, now we really cant count on having an instance or not, so
 *          we would at least have to do checks wether $this exists or not. 
 *          Some methods which come to mind are 'equals', 'toString' or 'hashCode'
 *          ( for those familiar with java, yes :-) )
**/
class Object extends stdClass
{
    /**
     * Convert an object to a string representation
     *
     * As PHP has its own __toString() magic method, we want to use that, but
     * that method can not be called explicitly. So we declare toString() in 
     * the interface so every object has it, but still reacts properly to __toString() 
     * method invocations by the engine itself (when converting internally to a string)
     * If customized behaviour is needed, override __toString() in your class.
     *
     * @return string
     * @todo php version 5.2 is ok for sure, 5.1.4 works, but manual says it 
     *       shouldnt work with sprintf(), keep an eye on it.
    **/
    public final function toString()
    {
        // Reuse __toString magic by internal conversion.
        return sprintf('%s',$this);
    }

    /**
     * Return the class for an object
     *
     * We want to be consistent with objects, so we need a class to model a class
     * PHP allows directly only get_class() or something like that, which 
     * returns a string. 
     * By defining a class called Class_ (note the underscore to prevent an name conflict)
     * we can get the class from each object and maintain the 'richness' of 
     * an object versus the 'flatness' of a string.
     *
     * @return Class_
     * @author Marcel van der Boom
    **/
    public final function getClass()
    {
        return new Class_($this);
    }
}

/**
 * A class to model a class in PHP
 *
 * The purpose of this class is mainly to support the getClass() method 
 * of the Object class above, but i can see it grow a bit further later on.
 * The class is final, there's only one definition of a class, it can not be
 * specialized in any way. Furthermore the constructor is made protected. 
 * In combination with the final keyword, this makes this class only instantiable
 * by its ancestors, which only is the Object class and is exactly what we want.
 *
 * @todo is the pass by reference needed?
 * @todo can we come up with a better name without the underscore?
**/
final class Class_ extends Object
{
    private $reflect = null;
    
    protected function __construct(Object &$object)
    {
        $this->reflect = new ReflectionClass($object);
    }
    
    public function getName()
    {
        return $this->reflect->getName();
    }

}
/**
 * The sys class contains routines guaranteed to be available to do small
 * things which we do a lot as fast as possible.
 *
 * The routines in this class should be:
 * - very well documented, since they may be unreadable for performance reasons
 * - as superfast as possible.
 * - depend on nothing
**/
final class sys extends Object
{
    const CONFIG = 'config.system.php';     // Default system configuration file
    
    private static $has = array();
    private static $var = null;
    
    private function __construct() 
    {} // no objects can be made out of this.
    
    /**
     * Import a xaraya component once, in the fastest way possible 
     *
     * Little utility function which allows easy inclusion of xaraya core 
     * components in the fastest (and safe) way
     * The dot path is mapped to the file to include as follows:
     * 
     * sys::once(a.b.c.d);  ~~ include_once(a/b/c/d.php); (only faster)
     *
     * WHY : this implementation is nearly constant time, no matter how many
     *       times you include a component. I've benched it against:
     *       - plain include_once inline,
     *       - include_once inside a function 
     *       - function with a static + include (procedural equivalent of this class)
     *       If you include something say not more than 2 to 3 times there is not
     *       much difference; if doing more than that, include_once is slower.
     *       This class and the procedural equivalent are nearly equal performing.
     *       PHP5 only obviously (tested against: 5.1.4-0.1 linux, 5.0.4 OSX)
     *
     * NOTE: only use this for class/function inclusion, they get included into
     *       the global scope. Any variables inside the include file will get
     *       the local scope of the line containing the include (which is here)
     *
     * NOTE: the line which does the actual inclusion could be faster by using
     *       include instead of include_once, but i couldnt measure much difference
     *       in practice. This is safer, because if there are still include_once's in
     *       the execution path, this class wont pick up that they have been 
     *       loaded, and will issue a 'cannot redeclare' warning. 
     *
     * @return mixed if file is actually included the return value determined by the included file, otherwise true
     * @param  string $dp 'dot path' a dot separated string describing which component to include
    **/
    private static function once($dp)
    {
        if(!isset(self::$has[$dp])) {
            // set this *before* the include below
            self::$has[$dp] = true; 
            // tiny bit faster would be to use include, but this is quite a bit safer
            // and it will be executed only once anyway. (i.e. if everything uses this class)
            return include_once(str_replace('.','/',$dp).'.php'); // I/O
        }
        return true;
    }
    
    /**
     * Public wrapper for the sys::once private method for components
     * 
     * Syntax examples:
     *    sys::import('blocklayout.compiler')              -> includes/blocklayout/compiler.php
     *    sys::import('modules.mymodule.xarincludes.test') -> modules/mymodule/xarincludes/test.php
     *
     * The beginning of the dot path is scanned for 'modules.' and 'creole.', 
     * if found it assumes a module/creole import 
     * is meant, otherwise a core component import is assumed.
     *
     * @see    sys::once()
     * @todo   do we want to support sys::import('blocklayout.*') ?
     * @todo   we should probably change our directory structure so we dont have to do specials for creole and modules.
    **/
    public static function import($dp)
    {
        if((0===strpos($dp,'modules.'))||(0===strpos($dp,'creole.'))) return self::once($dp);
        return self::once('includes.'.$dp);
    }
    
    /**
     * Returns the path name for the var directory
     *
     * The var directory may be placed outside the webroot. In this case
     * the var directory path should be placed in a file ./var/.key.php like:
     *
     * $protectedVarPath='/path/to/where/you/need/the/var/dir'; 
     * 
     * obviously the .key.php file must be a valid php file.
     *
     * @return string the var directory path name
     **/
     public static function varpath()
     {
         if (isset(self::$var)) return self::$var;
         if (file_exists('./var/.key.php')) { // I/O (prolly cheap, but we could eliminate this one with a try/catch)
             include './var/.key.php';        // I/O (doesnt use include_path, only looks in ./var/ so not that expensive either) 
             self::$var = $protectedVarPath;
         } else {
             self::$var = './var';
         }
         return self::$var;
     }
}
?>
