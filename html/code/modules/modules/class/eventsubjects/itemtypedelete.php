<?php
/**
 * ItemtypeDelete Hook Subject
 *
 * Notifies hooked observers when a module itemtype has been deleted
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('modules.modules.class.eventsubjects.apihook');
class ModulesItemtypeDeleteSubject extends ModulesApiHookSubject
{
    public $subject = 'ItemtypeDelete';
}
?>