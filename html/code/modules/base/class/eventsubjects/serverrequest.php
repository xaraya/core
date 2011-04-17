<?php
sys::import('xaraya.structures.events.subject');
class BaseServerRequestSubject extends EventSubject implements ixarEventSubject
{
    protected $subject = 'ServerRequest';
}
?>