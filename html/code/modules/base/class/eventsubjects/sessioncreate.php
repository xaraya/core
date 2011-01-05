<?php
sys::import('xaraya.structures.events.subject');
class BaseSessionCreateSubject extends EventSubject implements ixarEventSubject
{
    protected $subject = 'SessionCreate';
}
?>