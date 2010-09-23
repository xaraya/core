<?php
/**
 * ItemModify hook Subject
 *
 * Handles item modify hook observers (these typically return string of template data)
**/
/**
 * GUI type hook, observers should return string template data
**/
sys::import('modules.modules.class.eventsubjects.guihook');
class ModulesItemModifySubject extends ModulesGuiHookSubject
{
    public $subject = 'ItemModify';
    // methods inherited from parent...
}
?>