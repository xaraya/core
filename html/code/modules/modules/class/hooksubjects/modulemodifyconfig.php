<?php
/**
 * ModuleModifyconfig hook Subject
 *
 * Handles modifyconfig hook observers (these typically return string of template data)
**/
/**
 * GUI type hook, observers should return string template data
 * This hook should be called when displaying a modules admin modifyconfig function
 * It typically supplies per module/itemtype configuration settings from hooked modules 
 * Expected response from each observer is template data, usually form input
 *
 * The notify method returns an array of hookoutputs keyed by hooked module name
 * Called in modifyconfig function as...
 * $item = array('module' => 'modulename' [, 'itemtype' => int]);
 * New way of calling hooks
 * $data['hooks'] = xarHooks::notify('ModuleModifyconfig', $item);
 * Legacy way, supported for now, deprecated in future 
 * $data['hooks'] = xarModCallHooks('module', 'modifyconfig', 'modulename', $item); 
 * Output in modifyconfig template as
 * <xar:foreach in="$hooks" key="$hookmod" value="$hookoutput">
 *     #$hookoutput#
 * </xar:foreach>
**/
sys::import('xaraya.structures.hooks.guisubject');
class ModulesModuleModifyconfigSubject extends GuiHookSubject
{
    public $subject = 'ModuleModifyconfig';
    public function __construct($args=array())
    {
        // pass args to parent constructor, it validates module and extrainfo values 
        parent::__construct($args);
        // get args populated by constuctor array('objectid', 'extrainfo')
        $args = $this->getArgs();
        // Legacy Module observers expect an objectid with the name of the module 
        if (!isset($args['objectid'])) {
            // when called as xarHooks::notify() objectid will be empty
            // we instead get it from the module name in $args['extrainfo']
            $args['objectid'] = $args['extrainfo']['module'];
            // update args        
            $this->setArgs($args);    
        }        
        if (empty($args['objectid']) || !is_string($args['objectid']))
            throw new BadParameterException('objectid');

        // From this point on, any observers notified can safely assume arguments are valid
        // API and GUI observers will be passed $this->getArgs()
        // Class observers can obtain the same args from $subject->getArgs() or
        // just retrieve extrainfo from $subject->getExtrainfo() 
    } 
}
?>