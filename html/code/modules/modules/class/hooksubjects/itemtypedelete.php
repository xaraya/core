<?php
/**
 * ItemtypeDelete Hook Subject
 *
 * Notifies hooked observers when a module itemtype has been deleted
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('xaraya.structures.hooks.apisubject');
class ModulesItemtypeDeleteSubject extends ApiHookSubject
{
    public $subject = 'ItemtypeDelete';
}
?>