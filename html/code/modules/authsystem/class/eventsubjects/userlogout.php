<?php
sys::import('xaraya.structures.events.subject');
class AuthsystemUserLogoutSubject extends EventSubject implements ixarEventSubject
{
    protected $subject = 'UserLogout';
    public function __construct($userId)
    {
        parent::__construct($userId);                              
    }
}
?>