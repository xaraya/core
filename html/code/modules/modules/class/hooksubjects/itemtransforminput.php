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
class ModulesItemTransforminputSubject extends ApiHookSubject
{
    protected $subject = 'ItemTransforminput';
}
?>