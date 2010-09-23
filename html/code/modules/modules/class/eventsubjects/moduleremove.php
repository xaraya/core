<?php
/**
 * ModuleRemove Hook Subject
 *
 * Handles module remove hook observers (these typically return array of $extrainfo)
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('modules.modules.class.eventsubjects.apihook');
class ModulesModuleRemoveSubject extends ModulesApiHookSubject
{
    public $subject = 'ModuleRemove';
}
?>