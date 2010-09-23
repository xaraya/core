<?php
/**
 * ItemDisplay hook Subject
 *
 * Handles item display hook observers (these typically return string of template data)
**/
/**
 * GUI type hook, observers should return string template data
**/
sys::import('modules.modules.class.eventsubjects.guihook');
class ModulesItemDisplaySubject extends ModulesGuiHookSubject
{
    public $subject = 'ItemDisplay';
    // methods inherited from parent...
}
?>