<?php
/**
 * ItemFormarea hook Subject
 *
 * Handles item formarea hook observers (these typically return string of template data)
**/
/**
 * GUI type hook, observers should return string template data
**/
sys::import('xaraya.structures.hooks.guisubject');
class ModulesItemFormareaSubject extends GuiHookSubject
{
    public $subject = 'ItemFormarea';
    // methods inherited from parent...
}
?>