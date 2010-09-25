<?php
/**
 * ModuleRemove Hook Subject
 *
 * Handles module remove hook observers (these typically return array of $extrainfo)
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('xaraya.structures.hooks.apisubject');
class ModulesModuleRemoveSubject extends ApiHookSubject
{
    public $subject = 'ModuleRemove';
}
?>