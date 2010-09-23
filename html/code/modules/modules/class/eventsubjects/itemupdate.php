<?php
/**
 * ItemUpdate Hook Subject
 *
 * Notifies hooked observers when a module item has been updated
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('modules.modules.class.eventsubjects.apihook');
class ModulesItemUpdateSubject extends ModulesApiHookSubject
{
    public $subject = 'ItemUpdate';
}
?>