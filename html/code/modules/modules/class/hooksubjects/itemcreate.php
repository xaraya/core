<?php
/**
 * ItemCreate Hook Subject
 *
 * Notifies hooked observers when a module item has been created
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('xaraya.structures.hooks.apisubject');
class ModulesItemCreateSubject extends ApiHookSubject
{
    protected $subject = 'ItemCreate';
}
?>