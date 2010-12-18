<?php
/**
 * ModuleUpgrade Hook Subject
 *
 * Handles module upgrade hook observers (these typically return array of $extrainfo)
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('xaraya.structures.hooks.apisubject');
class ModulesModuleUpgradeSubject extends ApiHookSubject
{
    public $subject = 'ModuleUpgrade';
    // methods inherited from parent
}
?>