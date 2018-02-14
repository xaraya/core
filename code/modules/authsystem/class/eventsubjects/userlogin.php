<?php
/**
 * Authsystem Module
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/42.html
 */
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