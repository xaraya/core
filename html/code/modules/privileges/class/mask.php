<?php

/**
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 *
 * xarMask: class for the mask object
 *
 * Represents a single security mask
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
*/
class xarMask extends Object
{
    const PRIVILEGES_PRIVILEGETYPE = 2;
    const PRIVILEGES_MASKTYPE = 3;

    public $id;                    //the id of this privilege/mask
    public $name;                  //the name of this privilege/mask
    public $realm;                 //the realm of this privilege/mask
    public $module;                //the module name of this privilege/mask
    public $module_id;             //the module ID name of this privilege/mask
    public $component;             //the component of this privilege/mask
    public $instance;              //the instance of this privilege/mask
    public $level;                 //the access level of this privilege/mask
    public $description = '';      //the long description of this privilege/mask
    public $normalform;            //the normalized form of this privilege/mask

    public $privilegestable;
    public $privmemberstable;
    public $rolestable;
    public $acltable;
    public $modulestable;

    /**
     * xarMask: constructor for the class
     *
     * Creates a security mask
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   array of values
     * @return  void
    */
    function __construct($pargs=array())
    {
        extract($pargs);

        $xartable =& xarDB::getTables();
        $this->privilegestable = $xartable['privileges'];
        $this->privmemberstable = $xartable['privmembers'];
        $this->rolestable = $xartable['roles'];
        $this->acltable = $xartable['security_acl'];
        $this->realmstable = $xartable['security_realms'];
        $this->modulestable = $xartable['modules'];

        $this->id           = isset($id) ? (int) $id : 0;
        $this->name         = isset($name) ? $name : 'EmptyMask';
        $this->realm        = isset($realm) ? $realm : null;
        $this->module       = isset($module) ? $module : null;
        $this->component    = isset($component) ? $component : '';
        $this->instance     = isset($instance) ? $instance : '';
        $this->level        = isset($level) ? (int) $level : 0;
        $this->description  = isset($description) ? $description : '';
        if (!isset($module_id) || (in_array(strtolower($module), array('all','empty')))) {
            $this->setModuleID($this->module);
        } else {
            $this->module_id    = $module_id;
        }
    }

    function present()
    {
        $display = $this->getName();
        $display .= "-" . strtolower($this->getLevel());
        $display .= ":" . strtolower($this->getRealm());
        $display .= ":" . strtolower($this->getModule());
        $display .= ":" . strtolower($this->getComponent());
        $display .= ":" . strtolower($this->getInstance());
        return $display;
    }

    /**
     * normalize: creates a "normalized" array representing a mask
     *
     * Returns an array of strings representing a mask
     * The array can be used for comparisons with other masks
     * The function optionally adds "all"'s to the end of a normalized mask representation
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   integer   adds  Number of additional instance parts to add to the array
     * @return  array of strings
    */
    function normalize($adds=0)
    {
        if (isset($this->normalform)) {
            if (empty($adds)) return $this->normalform;
            $normalform = $this->normalform;
        } else {
            $normalform = array();
            $normalform['id'] = $this->getID();
            $normalform['name'] = $this->getName();
            $normalform['level'] = strtolower($this->getLevel());
            $normalform['realm'] = strtolower($this->getRealm());
            $normalform['module'] = $this->module_id;
            $normalform['component'] = strtolower($this->getComponent());
            $thisinstance = strtolower($this->getInstance());
            $instancearray = $this->getInstanceArray($thisinstance);
            
            // Cater to the myself role
            $normalinstance = array();
            foreach ($instancearray as $key => $value) 
                $normalinstance[$key] = $value == 'myself' ? xarSession::getVar('role_id') : $value;
                
            $normalform['instance']   = $normalinstance;
            $this->normalform = $normalform;
        }

/*        for ($i=0;$i<$adds;$i++) {
            $normalform[] = 'all';
        }
*/
        return $normalform;
    }
    
    /**
     * create an array representing a mask instance
    */
    private function getInstanceArray($instancestring=array()) 
    {
        if (empty($instancestring)) return array();
        // avoid phperrors exception handling if possible
        if (strpos($instancestring,'{') === false) {
            // the old way
            return explode(':', $instancestring);
        }
        try {
            // the new way - CHECKME: where is this used ?
            return unserialize($instancestring);
        } catch(Exception $e) {
            // the old way
            return explode(':', $instancestring);
        }
    }

    /**
     * canonical: returns 2 normalized privileges or masks as arrays for comparison
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   mask object
     * @return  array 2 normalized masks
    */
    function canonical($mask)
    {
        $p1 = $this->normalize();
        $p2 = $mask->normalize();

        return array($p1,$p2);
    }

    /**
     * matches: checks the structure of one privilege against another
     *
     * Checks whether two privileges, or a privilege and a mask, are equal
     * in all respects except for the access level
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   mask object
     * @return  boolean
    */
    function matches($mask)
    {
        list($p1,$p2) = $this->canonical($mask);
        $match = true;
        $p1count = count($p1);
        $p2count = count($p2);
        if ($p1count != $p2count) return false;
        for ($i=1; $i < $p1count; $i++) {
            $match = $match && ($p1[$i]==$p2[$i]);
        }
        return $match;
    }

    /**
     * matchesexactly: checks the structure of one privilege against another
     *
     * Checks whether two privileges, or a privilege and a mask, are equal
     * in all respects
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   mask object
     * @return  boolean
    */
    function matchesexactly($mask)
    {
        $match = $this->matches($mask);
        return $match && ($this->getLevel() == $mask->getLevel());
    }

    /**
     * includes: checks the structure of one privilege against another
     *
     * Checks a mask has the same or larger range than another mask
     *
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   mask object
     * @return  boolean
    */
    function includes($mask)
    {
        if (isset($this->normalform)) {
            $p1 = $this->normalform;
        } else {
            $p1 = $this->normalize();
        }
        if (isset($mask->normalform)) {
            $p2 = $mask->normalform;
        } else {
            $p2 = $mask->normalize();
        }
        // match realm. bail if no match.
        switch(xarModVars::get('privileges', 'realmcomparison')) {
            case "contains":
                $fails = $p1[1]!=$p2[1];
            case "exact":
            default:
                $fails = $p1[1]!=$p2[1];
                break;
        }
        if (($p1[1] != 'all') && ($fails)) return false;

        // match module and component. bail if no match.
        if (($p1[2] == null) || (($p1[2] != xarSecurity::PRIVILEGES_ALL) && ($p1[2]!=$p2[2]))) {
            return false;
        }
        if (($p1[3] != 'all') && ($p1[3]!=$p2[3])) {
            return false;
        }

        // now match the instances
        $p1count = count($p1);
        $p2count = count($p2);
        if($p1count != $p2count) {
            if($p1count > $p2count) {
                $p = $p2;
                $p2 = $mask->normalize($p1count - $p2count);
            } else {
                $p = $p1;
                $p1 = $this->normalize($p2count - $p1count);
            }
            if (count($p) != 5) {
                $msg = xarML('#(1) and #(2) do not have the same instances. #(3) | #(4) | #(5)',$mask->getName(),$this->getName(),implode(',',$p2),implode(',',$p1),$this->present() . "|" . $mask->present());
                throw new Exception($msg);
            }
        }
        for ( $i = 4, $p1count = count($p1); $i < $p1count; $i++) {
            if (($p1[$i] != 'all') && ($p1[$i]!=$p2[$i])) {
                return false;
            }
        }
        return true;
    }

    /**
     * implies: checks the structure of one privilege against another
     *
     * Checks a mask has the same or larger range, and the same or higher access right,
     * than another mask
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   mask object
     * @return  boolean
    */
    function implies($mask)
    {
        $match = xarSecurity::includes($this->normalform,$mask->normalform);
        return $match && ($this->getLevel() >= $mask->getLevel()) && ($mask->getLevel() > 0);
    }

    function getID()                 { return $this->id; }
    function getName()                 { return $this->name; }
    function getRealm()             { return ($this->realm == null) ? "All" : $this->realm; }
    function getModule()             { return ($this->module_id == 0) ? "All" : $this->module; }
    function getModuleID()            { return $this->module_id; }
    function getComponent()         { return $this->component; }
    function getInstance()             { return $this->instance; }
    function getLevel()             { return $this->level; }
    function getDescription()        { return $this->description; }

    function setName($var)             { $this->name = $var; }
    function setRealm($var)         { $this->realm = $var; }
    function setModule($var)         { $this->module = $var; }
    function setModuleID($var)
    {
        if (strtolower($var) == 'all') $this->module_id = xarSecurity::PRIVILEGES_ALL;
        elseif (($var === null) || (strtolower($var) == 'empty')) $this->module_id = null;
        else $this->module_id = xarMod::getID($var);
    }
    function setComponent($var)     { $this->component = $var; }
    function setInstance($var)         { $this->instance = $var; }
    function setLevel($var)         { $this->level = $var; }
    function setDescription($var)     { $this->description = $var; }


}
?>