<?php
/**
 * ModuleActivate Hook Subject
 *
 * Notifies hooked observers when a module has been activated
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('xaraya.structures.hooks.apisubject');
class ModulesModuleActivateSubject extends ApiHookSubject
{
    public $subject = 'ModuleActivate';
}
?>