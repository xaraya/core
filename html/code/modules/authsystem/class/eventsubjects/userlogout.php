<?php
sys::import('xaraya.structures.events.subject');

/**
 * Authsystem User Subject for logout events
 */
class AuthsystemUserLogoutSubject extends EventSubject implements ixarEventSubject
{
    protected $subject = 'UserLogout';
    
    /**
     * Constructor
     * 
     * @param int $userId
     */
    public function __construct($userId)
    {
        parent::__construct($userId);                              
    }
}
?>