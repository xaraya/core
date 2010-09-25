<?php
sys::import('xaraya.structures.events.subject');
class AuthsystemUserLoginSubject extends EventSubject implements ixarEventSubject
{
    protected $subject = 'UserLogin';
    public function __construct($userId)
    {
        parent::__construct($userId);                             
    }
}
?>