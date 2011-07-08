<?php
/**
 * ItemFormaction hook Subject
 *
 * Handles item formaction hook observers (these typically return string of template data)
**/
/**
 * GUI type hook, observers should return string template data
**/
sys::import('xaraya.structures.hooks.guisubject');
class ModulesItemFormactionSubject extends GuiHookSubject
{
    public $subject = 'ItemFormaction';
    // methods inherited from parent...
}
?>