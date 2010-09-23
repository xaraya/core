<?php
/**
 * ItemtypeCreate Hook Subject
 *
 * Notifies hooked observers when a module itemtype has been created
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('modules.modules.class.eventsubjects.apihook');
class ModulesItemtypeCreateSubject extends ModulesApiHookSubject
{
    public $subject = 'ItemtypeCreate';
}
?>