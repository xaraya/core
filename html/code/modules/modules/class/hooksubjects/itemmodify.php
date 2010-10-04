<?php
/**
 * ItemModify hook Subject
 *
 * Handles item modify hook observers (these typically return string of template data)
**/
/**
 * GUI type hook, observers should return string template data
 *
 * The notify method returns an array of hookoutputs keyed by hooked module name
 * Called in modify function as...
 * $item = array('module' => $module, $itemid => $itemid [, 'itemtype' => $itemtype, ...]);
 * New way of calling hooks
 * $data['hooks'] = xarHooks::notify('ItemModify', $item);
 * Legacy way, supported for now, deprecated in future 
 * $data['hooks'] = xarModCallHooks('item', 'modify', $itemid, $item); 
 * Output in modify template as
 * <xar:foreach in="$hooks" key="$hookmod" value="$hookoutput">
 *     #$hookoutput#
 * </xar:foreach>
**/
sys::import('xaraya.structures.hooks.guisubject');
class ModulesItemModifySubject extends GuiHookSubject
{
    public $subject = 'ItemModify';
    
    public function __construct($args=array())
    {
        // pass args to parent constructor, it validates module and extrainfo values 
        parent::__construct($args);
        // get args populated by constuctor array('objectid', 'extrainfo')
        $args = $this->getArgs();
        // Item observers expect an objectid, if it isn't valid it's pointless notifying them, bail
        if (!isset($args['objectid']) || !is_numeric($args['objectid']))
            throw new BadParameterException('objectid');
        // From this point on, any observers notified can safely assume arguments are valid
        // API and GUI observers will be passed $this->getArgs()
        // Class observers can obtain the same args from $subject->getArgs() or
        // just retrieve extrainfo from $subject->getExtrainfo() 
    } 
}
?>