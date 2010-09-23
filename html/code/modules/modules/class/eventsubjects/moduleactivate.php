<?php
/**
 * ModuleActivate Hook Subject
 *
 * Notifies hooked observers when a module has been activated
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('modules.modules.class.eventsubjects.apihook');
class ModulesModuleActivateSubject extends ModulesApiHookSubject
{
    public $subject = 'ModuleActivate';
}
?>