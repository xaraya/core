<?php
/**
 * ItemCreate Hook Subject
 *
 * Notifies hooked observers when a module item has been created
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('modules.modules.class.eventsubjects.apihook');
class ModulesItemCreateSubject extends ModulesApiHookSubject
{
    public $subject = 'ItemCreate';
}
?>