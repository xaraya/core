<?php
sys::import('modules.base.class.eventsubjects.event');
class BaseSessionCreateSubject extends BaseEventSubject implements ixarEventSubject
{
    protected $subject = 'SessionCreate';
}
?>