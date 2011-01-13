<?php
/**
 * ItemTransform Hook Subject
 *
 * Notifies hooked observers when some item fields are to be transformed
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('xaraya.structures.hooks.apisubject');
class ModulesItemTransformSubject extends ApiHookSubject
{
    protected $subject = 'ItemTransform';
}
?>