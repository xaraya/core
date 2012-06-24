<?php
/**
 * @package core
 * @subpackage hooks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */
/**
 * HookSubject
 *
 * NOTE: this class is never called directly, but should be extended
 * by other hook subjects. Hook subjects should only need to extend this
 * class and overload the $subject property with their event subject name,
 * the inherited methods will take care of the rest
**/
sys::import('xaraya.structures.events.subject');
// declared abstract to prevent direct instances of this class
abstract class HookSubject extends EventSubject implements ixarEventSubject, ixarHookSubject
{
    // protected $args; // from EventSubject
    protected $subject = 'Hook'; // change this to the name of your hook subject    
    protected $extrainfo = array();

    /**
     * constructor
     * This is common to all hook subjects
     * 
     * @param array $args, array containing hook caller item params and values
     * @return void
     * @throws BadParameterException
    **/    
    public function __construct($args=array())
    {   
        // The basic premise here is to support legacy hooks using (array('objectid', 'extrainfo'))
        // whilst allowing a more sane approach for hook observers written as classes
        // in $this->args we will store the array for legacy hook functions
        // when notified, the EMS passes $this->getArgs() to legacy hook functions for us
        // in $this->args[extrainfo] we will store the array for class based observers
        // when notified, the observer can obtain extrainfo in one hit from $subject->getExtrainfo();    
        extract($args);
        // Hooks called using xarHooks::notify() $args is extrainfo
        // Hooks called using xarModCallHooks, $args is an array containing objectid, and extrainfo
        if (empty($extrainfo)) $extrainfo = $args;
        
        // extrainfo must be an array
        if (!is_array($extrainfo))
            throw new BadParameterException('extrainfo');
        
        if (empty($module)) {
            if (!empty($extrainfo['module'])) {
                $module = $extrainfo['module'];
            } else {
                list($module) = xarController::$request->getInfo();
            }        
        }        

        // No module_id given here raises an exception        
        $module_id = xarMod::getRegID($module);
        if (empty($module_id))
            throw new BadParameterException('module');
        
        // it's ok for the itemtype to be empty
        if (empty($itemtype) && isset($extrainfo['itemtype']))
            $itemtype = $extrainfo['itemtype'];
        if (!isset($itemtype))
            $itemtype = null;
        // if itemtype is set, it must be numeric
        if (isset($itemtype) && !is_numeric($itemtype))
            throw new BadParameterException('itemtype');

        // it's ok for the objectid to be empty (eg, for module, and itemtype scope hooks)
        if (empty($objectid) && isset($extrainfo['itemid']))
            $objectid = $extrainfo['itemid'];
        if (empty($objectid))
            $objectid = 0;
        // object id may be a string here, so we can't do type validation,
        // we leave that to the subjects overloading this method 
        
        // Populate extrainfo with any missing params
        if (!isset($extrainfo['module']))
            $extrainfo['module'] = $module;
        
        if (!isset($extrainfo['module_id']))
            $extrainfo['module_id'] = $module_id;
        
        if (!isset($extrainfo['itemtype']))
            $extrainfo['itemtype'] = $itemtype;
        
        if (!isset($extrainfo['itemid']))
            $extrainfo['itemid'] = $objectid;

        // Populate the array of arguments each observer can expect
        $args = array(
            'objectid' => $objectid,
            'extrainfo' => $extrainfo,
        );
        // Call the parent constructor
        parent::__construct($args);
    }
    
    public function getExtrainfo()
    {
        $args = $this->getArgs();
        if (isset($args['extrainfo'])) 
            return $args['extrainfo'];
    }
}

interface ixarHookSubject
{
    public function getExtrainfo();
}
?>
