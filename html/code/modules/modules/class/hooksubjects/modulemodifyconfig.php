<?php
/**
 * ModuleModifyconfig hook Subject
 *
 * Handles modifyconfig hook observers (these typically return string of template data)
**/
/**
 * GUI type hook, observers should return string template data
**/
sys::import('xaraya.structures.hooks.guisubject');
class ModulesModuleModifyconfigSubject extends GuiHookSubject
{
    public $subject = 'ModuleModifyconfig';
    // methods inherited from parent...
}
?>