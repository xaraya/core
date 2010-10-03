<?php
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
    // Hook subjects can optionally supply other properties for their observers to use  

    /**
     * constructor
     * This is common to all hook subjects
     * @CHECKME: declare final, shouldn't need to be overloaded ?
     * @FIXME: this could be improved upon
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
    
        // all validation happens here
        extract($args);
             
        if (empty($extrainfo))
            $extrainfo = array();
        if (!is_array($extrainfo)) 
            throw new BadParameterException('extrainfo');
        
        if (empty($module)) {
            if (!empty($extrainfo['module'])) {
                $module = $extrainfo['module'];
            } else {
                // @CHECKME: is module name ever omitted for this to happen? 
                list($module) = xarController::$request->getInfo();
            }
        }
        $module_id = xarMod::getRegID($module);
        if (empty($module_id)) 
            throw new BadParameterException('module');
        
        if (empty($itemtype) && !empty($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype']))
            $itemtype = $extrainfo['itemtype'];
        if (!empty($itemtype) && !is_numeric($itemtype)) 
            throw new BadParameterException('itemtype');
        if (empty($itemtype)) $itemtype = null;
        
        if (empty($objectid)) {
            // check for item id passed in $args (new way)            
            if (!empty($id)) {
                $objectid = $id;
            } elseif (!empty($itemid)) {
                $objectid = $itemid;
            }          
        }
        // check for item id passed in extrainfo (old way)
        if (empty($objectid) && !empty($extrainfo['itemid']))
            $objectid = $extrainfo['itemid'];
        // NOTE: we can't check var type numeric, since objectid can be a module name, eg for ModuleRemove
        if (empty($objectid))
            throw new BadParameterException('objectid');
            
        // this is the minimum data the hook observer can always expect in extrainfo 
        $extrainfo['itemid'] = $objectid;
        $extrainfo['module'] = $module;
        $extrainfo['module_id'] = $module_id;
        $extrainfo['itemtype'] = $itemtype;
        // merge any args not already in extrainfo
        foreach ($args as $k => $v) {
            if ($k == 'extrainfo' || isset($extrainfo[$k])) continue;
            $extrainfo[$k] = $v;
        } 
        $args = array(
            'objectid' => $objectid,
            'extrainfo' => $extrainfo,
        );
        // set $args

        parent::__construct($args);
    }
    /**
     * getExtrainfo
     * This is common to all hook subjects
     * @CHECKME: declare final, shouldn't need to be overloaded ?
     * 
     * @params none
     * @return array extrainfo for the current hook item 
     * @throws none
    **/        
    public function getExtrainfo()
    {
        $args = $this->getArgs();
        if (isset($args['extrainfo'])) 
            return $args['extrainfo'];
    }
    // The default hook subject inherits all other methods from the EventSubject
    // Hook subjects may optionally overload those methods,
    // and/or supply other methods for their observers to use   
    
}
// All hook subjects must implement this interface...
interface ixarHookSubject
{
    public function getExtraInfo();
}

?>
