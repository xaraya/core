<?php
/**
 * ItemDisplay hook Subject
 *
 * Handles item display hook observers (these typically return string of template data)
**/
/**
 * GUI type hook, observers should return string template data
**/
sys::import('xaraya.structures.hooks.guisubject');
class ModulesItemDisplaySubject extends GuiHookSubject
{
    public $subject = 'ItemDisplay';
    // methods inherited from parent...
}
?>