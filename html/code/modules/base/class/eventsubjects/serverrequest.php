<?php
sys::import('modules.base.class.eventsubjects.event');
class BaseServerRequestSubject extends BaseEventSubject implements ixarEventSubject
{
    protected $subject = 'ServerRequest';
}
?>