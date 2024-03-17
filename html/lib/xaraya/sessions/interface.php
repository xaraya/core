<?php
/**
 * @package core\sessions
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Sessions;

/**
 * Interface between xarSession (static) and SessionHandler (instance)
 * Note: if you want to replace SessionHandler with a custom class, use
 * xarSession::setSessionClass(SessionContext::class);
 */
interface SessionInterface
{
    /**
     * Constructor for the session handler
     * @param array<string, mixed> $args not by reference anymore
     * @uses xarSession::setInstance()
     * @return void
     **/
    public function __construct($args);

    /**
     * Initialize the session after setup
     * @return bool
     */
    public function initialize();

    /**
     * Get (or set) the session id
     * @param ?string $id
     * @return string|bool
     */
    public function getId($id = null);

    /**
     * Get a session variable
     * @param string $name name of the session variable to get
     * @return mixed
     */
    public function getVar($name);

    /**
     * Set a session variable
     * @param string $name name of the session variable to set
     * @param mixed $value value to set the named session variable
     * @return bool
     */
    public function setVar($name, $value);

    /**
     * Delete a session variable
     * @param string $name name of the session variable to delete
     * @return bool
     */
    public function delVar($name);

    /**
     * Set user info
     * @param int $userId
     * @param int $rememberSession
     * @todo this seems a strange duck (only used in roles by the looks of it)
     * @return bool
     */
    public function setUserInfo($userId, $rememberSession);

    /**
     * Unset current session variables
     * @return void
     */
    public function unsetVars();

    /**
     * Clear all the sessions in the sessions table
     * @param array<mixed> $spared a list of roles IDs whose sessions are left untouched
     * @return bool
     */
    public function clear($spared = []);
}
