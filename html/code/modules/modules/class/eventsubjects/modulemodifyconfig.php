<?php
/**
 * ModuleModifyconfig hook Subject
 *
 * Handles modifyconfig hook observers (these typically return string of template data)
**/
/**
 * GUI type hook, observers should return string template data
**/
sys::import('modules.modules.class.eventsubjects.guihook');
class ModulesModuleModifyconfigSubject extends ModulesGuiHookSubject
{
    public $subject = 'ModuleModifyconfig';
    // methods inherited from parent...
}
?>