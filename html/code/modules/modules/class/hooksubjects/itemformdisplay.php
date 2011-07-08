<?php
/**
 * ItemFormdisplay hook Subject
 *
 * Handles item formdisplay hook observers (these typically return string of template data)
**/
/**
 * GUI type hook, observers should return string template data
**/
sys::import('xaraya.structures.hooks.guisubject');
class ModulesItemFormdisplaySubject extends GuiHookSubject
{
    public $subject = 'ItemFormdisplay';
    // methods inherited from parent...
}
?>