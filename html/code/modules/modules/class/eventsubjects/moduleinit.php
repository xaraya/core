<?php
/**
 * ModuleInit Hook Subject
 *
 * Notifies hooked observers when a module has been initialised
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('modules.modules.class.eventsubjects.apihook');
class ModulesModuleInitSubject extends ModulesApiHookSubject
{
    public $subject = 'ModuleInit';
}
?>