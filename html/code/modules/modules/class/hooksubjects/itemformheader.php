<?php
/**
 * ItemFormheader hook Subject
 *
 * Handles item formheader hook observers (these typically return string of template data)
**/
/**
 * GUI type hook, observers should return string template data
**/
sys::import('xaraya.structures.hooks.guisubject');
class ModulesItemFormheaderSubject extends GuiHookSubject
{
    public $subject = 'ItemFormheader';
    // methods inherited from parent...
}
?>