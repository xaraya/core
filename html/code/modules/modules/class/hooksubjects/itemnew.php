<?php
/**
 * ItemNew hook Subject
 *
 * Handles item new hook observers (these typically return string of template data)
**/
/**
 * GUI type hook, observers should return string template data
**/
sys::import('xaraya.structures.hooks.guisubject');
class ModulesItemNewSubject extends GuiHookSubject
{
    public $subject = 'ItemNew';
    // methods inherited from parent...
}
?>