<?php
/**
 * ModuleUpdateconfig Hook Subject
 *
 * Handles updateconfig hook observers (these typically return array of $extrainfo)
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('xaraya.structures.hooks.apisubject');
class ModulesModuleUpdateconfigSubject extends ApiHookSubject
{
    public $subject = 'ModuleUpdateconfig';
    // methods inherited from parent
}
?>