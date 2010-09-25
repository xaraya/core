<?php
/**
 * ItemModify hook Subject
 *
 * Handles item modify hook observers (these typically return string of template data)
**/
/**
 * GUI type hook, observers should return string template data
**/
sys::import('xaraya.structures.hooks.guisubject');
class ModulesItemModifySubject extends GuiHookSubject
{
    public $subject = 'ItemModify';
    // methods inherited from parent...
}
?>