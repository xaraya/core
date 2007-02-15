<?php
/**
 * Bootstrap file
 *
 * This file is the only one (longer term) which always gets included when
 * running Xaraya. Everything else is lazy loaded. This file contains the
 * things which *absolutely* need to be available, which should be very little.
 *
 * So far:
 *  - Declaration of the Object   class of which all other classes are derived
 *  - Declaration of the Class_   class which Metamodels a PHP class.
 *  - Declaration of the Property class which Metamodels a PHP property
 *  - Definition of the sys class which contains methods to steer php in the right direction
 *    for getting the right files (now: inclusion and the var path)
 *
 * If anything, make absolutely sure you get the fastest implementation
 * of what you want to do here.
 *
 * @package core
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marcel van der Boom <mrb@hsdev.com>
**/

/**
 * The Object class from which all other classes should be derived.
 *
 * This is basically a placeholder extending from stdClass so we have a
 * place to put things in our root class. There are severe limitations to what
 * can and can not be placed into this class. For example, it can not have a
 * constructor because it would prevent descendents to have a private
 * constructor, which is rather common in the SingleTon pattern.
 *
 * @package core
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
     * @return string string representation of the object.
     * @todo php version 5.2 is ok for sure, 5.1.4/5.1.6 works, but manual says it
     *       shouldnt work with sprintf(), keep an eye on it.
    **/
    final public function toString()
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
     * By defining a class called Class_ (note the underscore to prevent a name conflict)
     * we can get the class from each object and maintain the 'richness' of
     * an object versus the 'flatness' of a string.
     *
     * @return Class_ the class of the object
    **/
    final public function getClass()
    {
        return new Class_($this);
    }

    /**
     * Determine equality of two objects
     *
     * We do this because it allows to make the comparison overridable and
     * pair it up with the hashCode method
     * Usually when overriding equals or hashCode, you will want to override
     * the other method too.
     * Note: $this === $object is the same here, but this way just overriding
     * hashCode allows for equality specialisation.
    **/
    public function equals(Object $object)
    {
        return $this->hashCode() === $object->hashCode();
    }

    /**
     * A unique id for an object
     *
     *
    **/
    public function hashCode()
    {
        return spl_object_hash($this);
    }

    /**
     * Get an array of property values
     *
     * @todo why not deliver a Property[] instead?
     * @todo the public part is something that probably belongs in the caller, not here
     * @todo it doesnt get properties ;-)
    **/
    public function getPublicProperties()
    {
        $properties = array();
        $cl = $this->getClass();
        foreach($cl->getProperties() as $ix => $p) {
            if ($p->isPublic())
                $properties[$p->getName()] = $p->getValue($this);
        }
        return $properties;
    }
}

/**
 * Base class for the reflectable objects we will expose
 *
 * @package core
**/
abstract class Reflectable extends Object
{
    protected $reflect = null;

    public function getName()
    {
        return $this->reflect->getName();
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
 * @package core
 * @todo can we come up with a better name without the underscore?
**/
final class Class_ extends Reflectable
{
    protected function __construct(Object $object)
    {
        $this->reflect = new ReflectionClass($object);
    }

    public function getProperties()
    {
        $ret = array();
        foreach($this->reflect->getProperties() as $p)
            $ret[] = new Property($this, $p->getName());
        return $ret;
    }

    /**
     * Return a property from an instance of an Object
     *
     * @param  string   Name of the property
     * @return Property Property object
     * @todo get rid of the underscore once DataPropertyMaster:getProperty is remodelled
    **/
    final public function getProperty_($name)
    {
        return new Property($this,$name);
    }
}

/**
 * A class to model a property in PHP
 *
 * The purpose of this class i mainly to support the getProperty_() method
 * in the Object class above, but i can see it grow a bit futher later on.
 * The class is final, there's only one definition of a property, it can not be
 * specialized in any way. The constructor is public here because of the getProperty
 * method in the Class_ class.
 *
 * @package core
**/
final class Property extends Reflectable
{
    public function __construct(Class_ $clazz, $name)
    {
        $this->reflect = new ReflectionProperty($clazz->getName(),$name);
    }

    public function isPublic()
    {
        return $this->reflect->isPublic();
    }

    public function getValue(Object $object)
    {
        return $this->reflect->getValue($object);
    }
}

/**
 * The sys class contains routines guaranteed to be available to do small
 * things which we do a lot as fast as possible.
 *
 * The routines in this class should be:
 * - very well documented, since they may be unreadable for performance reasons
 * - as superfast as possible.
 * - depend on nothing but itself and assumptions we make for the whole framework
 *
 * @package core
**/
final class sys extends Object
{
    const CONFIG = 'config.system.php';     // Default system configuration file

    private static $has  = array();         // Keep a list of what we already have
    private static $var  = null;            // Save the var location
    private static $root = null;            // Save our root location

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
     * @todo   the absolute path fixes the location to one dir below the webroot
    **/
    private static function once($dp)
    {
        // If we already have it get out of here asap
        if(!isset(self::$has[$dp]))
        {
            // set this *before* the include below
            self::$has[$dp] = true;
            // tiny bit faster would be to use include, but this is quite a bit safer
            // and it will be executed only once anyway. (i.e. if everything uses this class)
            return include_once(self::root() . '/' . str_replace('.','/',$dp).'.php');
        }
        return true;
    }

    /**
     * Public wrapper for the sys::once private method for components
     *
     * Syntax examples:
     *    sys::import('blocklayout.compiler')              -> lib/blocklayout/compiler.php
     *    sys::import('modules.mymodule.xarincludes.test') -> html/modules/mymodule/xarincludes/test.php
     *
     * The beginning of the dot path is scanned for 'modules.'
     * if found it assumes a module import
     * is meant, otherwise a core component import is assumed.
     *
     * @see    sys::once()
     * @todo   do we want to support sys::import('blocklayout.*') ?
     * @todo   we should probably change our directory structure so we dont have to do specials for modules.
    **/
    public static function import($dp)
    {
        if((0===strpos($dp,'modules.'))) return self::once('html.' . $dp);
        return self::once('lib.'.$dp);
    }

    /**
     * Returns the absolute path of the xaraya system root, NOT the web root
     * Note that there will be NO slash at the end of the returne path.
     *
     * @return string
    **/
    public static function root()
    {
        // We are in <root>/lib/bootstrap.php and we want <root>
        if(!isset(self::$root))
            self::$root = dirname(dirname(realpath(__FILE__)));
        return self::$root;
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
     * @todo the .key.php construct seems odd
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

/**
 * The DataContainer class from which all classes containing data are derived
 *
 * This class has the minimum methods for subclasses that
 * are not "system" classes and need to interact with other Xaraya classes.
 *
 * [random]
 *     The specific use case is:
 *     collections with the idea that in the future sometime a standard "get"
 *     function like we have them in modules now will return an object
 *     and a getrall will return a collection
 *
 * @package core
**/
class DataContainer extends Object
{
    /**
     *  @todo protected members cannot be gotten?
     *  @todo <mrb> i dont think this is a feasible direction
    **/
    public function get($name)
    {
        $p = $this->getProperty_($name);
        if($p->isPublic())
            return $p->$name;
    }

    /**
     *  @todo protected members cannot be set?
     *  @todo <mrb> i dont think this is a feasible direction
    **/
    public function set($name, $value)
    {
        $p = $this->getProperty_($name);
        if($p->isPublic())
            $this->$name = $value;
    }
}

?>
