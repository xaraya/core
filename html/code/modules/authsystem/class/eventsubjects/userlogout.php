<?php
sys::import('modules.base.class.eventsubjects.event');
class AuthsystemUserLogoutSubject extends BaseEventSubject implements ixarEventSubject
{
    protected $subject = 'UserLogout';
    public function __construct($userId)
    {
        $args = array();
        if (!empty($userId)) $args['id'] = $userId;
        parent::__construct($args); // $this->setArgs($args);                              
    }
}
?>