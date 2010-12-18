<?php
/**
 * ItemtypeCreate Hook Subject
 *
 * Notifies hooked observers when a module itemtype has been created
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('xaraya.structures.hooks.apisubject');
class ModulesItemtypeCreateSubject extends ApiHookSubject
{
    public $subject = 'ItemtypeCreate';
}
?>