<?php
/**
 * ItemUpdate Hook Subject
 *
 * Notifies hooked observers when a module item has been updated
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('xaraya.structures.hooks.apisubject');
class ModulesItemUpdateSubject extends ApiHookSubject
{
    public $subject = 'ItemUpdate';
}
?>