<?php
/**
 * ItemSubmit Hook Subject
 *
 * Notifies hooked observers when a module item has been submitted
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('xaraya.structures.hooks.apisubject');
class ModulesItemSubmitSubject extends ApiHookSubject
{
    public $subject = 'ItemSubmit';
}
?>