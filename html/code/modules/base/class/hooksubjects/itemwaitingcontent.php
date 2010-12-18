<?php
/**
 * ItemWaitingcontent Hook Subject
 *
 * Notifies hooked observers when displaying waiting content block
 * @FIXME: this should be ModuleWaitingcontent
**/
/**
 * GUI type hook, observers should return array of $extrainfo
**/
sys::import('xaraya.structures.hooks.guisubject');
class BaseItemWaitingcontentSubject extends GuiHookSubject
{
    public $subject = 'ItemWaitingcontent';
}
?>