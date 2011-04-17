<?php
/**
 * ItemtypeView hook Subject
 *
 * Handles itemtype view hook observers (these typically return string of template data)
**/
/**
 * GUI type hook, observers should return string template data
**/
sys::import('xaraya.structures.hooks.guisubject');
class ModulesItemtypeViewSubject extends GuiHookSubject
{
    public $subject = 'ItemtypeView';
    // methods inherited from parent...
}
?>