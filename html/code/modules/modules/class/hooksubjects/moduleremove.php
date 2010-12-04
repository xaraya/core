<?php
/**
 * ModuleRemove Hook Subject
 *
 * Handles module remove hook observers (these typically return array of $extrainfo)
**/
/**
 * API type hook, observers should return array of $extrainfo
 *
 * The notify method returns an array of cumulative extrainfo from the observers
 * Called in (api|gui) function after item is created as...
 * $item = array('module' => $module);
 * New way of calling hooks
 * xarHooks::notify('ModuleRemove', $item);
 * Legacy way, supported for now, deprecated in future 
 * xarModCallHooks('module', 'remove', $module, $item); 
**/
sys::import('xaraya.structures.hooks.apisubject');
class ModulesModuleRemoveSubject extends ApiHookSubject
{
    public $subject = 'ModuleRemove';

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