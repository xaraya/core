<?php
/**
 * HookSubject
 *
 * NOTE: this class is never called directly, but should be extended
 * by gui type hook subjects. Hook subjects should only need to extend this
 * class and overload the $subject property with their event subject name,
 * the inherited methods will take care of the rest
**/
// inherit properties and methods of base event class 
sys::import('modules.base.class.eventsubjects.event');
// declared abstract to prevent direct instances of this class
abstract class ModulesHookSubject extends BaseEventSubject
{
    public $subject = 'Hook'; // change this to the name of your event subject

    /**
     * constructor
     * This is common to all hook subjects
     * declared final, should not be overloaded
     * 
    **/    
    final public function __construct($args=array())
    {
        extract($args);
        // all validation happens here, so hook observers never need to check
        if (empty($extrainfo))
            $extrainfo = array();
        if (!is_array($extrainfo))
            // throw exception, event system should capture this 
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
        if (empty($itemtype) && !empty($extrainfo['itemtype']))
            $itemtype = $extrainfo['itemtype'];
        if (isset($itemtype) && !is_numeric($itemtype))
            throw new BadParamerException('itemtype');
        if (!empty($id) && empty($itemid))
            $itemid = $id;
        if (!empty($itemid) && empty($objectid))
            $objectid = $itemid;
        if (empty($objectid) && !empty($extrainfo['itemid']))
            $objectid = $extrainfo['itemid'];
        if (empty($objectid))
            throw new BadParameterException('objectid');
        // this is the minimum data the hook observer can always expect in extrainfo 
        $extrainfo['itemid'] = $objectid;
        $extrainfo['module'] = $module;
        $extrainfo['module_id'] = $module_id;
        $extrainfo['itemtype'] = isset($itemtype) ? $itemtype : null;
        // merge any args not already in extrainfo
        foreach ($args as $k => $v) {
            if ($k == 'extrainfo' || isset($extrainfo[$k])) continue;
            $extrainfo[$k] = $v;
        }       
        // update args
        // @TODO: deprecate use of objectid here, id or subject are more apt
        // These are the two arguments hook observers can always expect
        $args['objectid'] = $objectid;
        $args['extrainfo'] = $extrainfo;
        // set $args
        parent::__construct($args);
    }
    
    public function getExtrainfo()
    {
        $args = $this->getArgs();
        if (isset($args['extrainfo'])) 
            return $args['extrainfo'];
    }
}
?>