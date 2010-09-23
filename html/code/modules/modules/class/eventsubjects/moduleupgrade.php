<?php
/**
 * ModuleUpgrade Hook Subject
 *
 * Handles module upgrade hook observers (these typically return array of $extrainfo)
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('modules.modules.class.eventsubjects.apihook');
class ModulesModuleUpgradeSubject extends ModulesApiHookSubject
{
    public $subject = 'ModuleUpgrade';
    // methods inherited from parent
}
?>