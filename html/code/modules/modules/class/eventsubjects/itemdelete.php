<?php
/**
 * ItemDelete Hook Subject
 *
 * Notifies hooked observers when a module item has been deleted
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('modules.modules.class.eventsubjects.apihook');
class ModulesItemDeleteSubject extends ModulesApiHookSubject
{
    public $subject = 'ItemDelete';
}
?>