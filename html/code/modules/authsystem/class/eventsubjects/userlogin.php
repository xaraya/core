<?php
sys::import('xaraya.structures.events.subject');

/**
 * Authsystem User Subject for Login event
 */
class AuthsystemUserLoginSubject extends EventSubject implements ixarEventSubject
{
    protected $subject = 'UserLogin';
    
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