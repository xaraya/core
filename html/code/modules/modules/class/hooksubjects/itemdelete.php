<?php
/**
 * ItemDelete Hook Subject
 *
 * Notifies hooked observers when a module item has been deleted
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('xaraya.structures.hooks.apisubject');
class ModulesItemDeleteSubject extends ApiHookSubject
{
    public $subject = 'ItemDelete';
}
?>