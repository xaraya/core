<?php
/**
 * ModuleUpdateconfig Hook Subject
 *
 * Handles updateconfig hook observers (these typically return array of $extrainfo)
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('modules.modules.class.eventsubjects.apihook');
class ModulesModuleUpdateconfigSubject extends ModulesApiHookSubject
{
    public $subject = 'ModuleUpdateconfig';
    // methods inherited from parent
}
?>