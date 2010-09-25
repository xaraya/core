<?php
/**
 * ModuleInit Hook Subject
 *
 * Notifies hooked observers when a module has been initialised
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('xaraya.structures.hooks.apisubject');
class ModulesModuleInitSubject extends ApiHookSubject
{
    public $subject = 'ModuleInit';
}
?>