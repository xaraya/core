<?php
/**
 * @package core\context
 * @subpackage context
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Context;

use ArrayObject;
use sys;

sys::import('xaraya.context.interface');
sys::import('xaraya.context.factory');
sys::import('xaraya.requests.context');
sys::import('xaraya.sessions.context');
sys::import('xaraya.context.user');

/**
 * Context object for request etc.
 * @template TKey of array-key
 * @template TValue of mixed
 * @extends ArrayObject<TKey, TValue>
 */
class Context extends ArrayObject implements ContextInterface
{
    /**
     * Get current requestId
     * @return string|null
     */
    public function getRequestId()
    {
        if (!$this->offsetExists('requestId')) {
            return null;
        }
        return $this->offsetGet('requestId');
    }

    /**
     * Get current session (if any)
     * @return mixed
     */
    public function getSession()
    {
        if (!$this->offsetExists('session')) {
            return null;
        }
        return $this->offsetGet('session');
    }

    /**
     * Get current userId - entrypoint for session in rest handler and graphql
     * @return int|null
     */
    public function getUserId()
    {
        if (!$this->offsetExists('userId')) {
            $userContext = new UserContext($this);
            $userId = $userContext->getUserId();
            $this->offsetSet('userId', $userId);
        }
        return $this->offsetGet('userId');
    }

    /**
     * Set current userId
     * @param int $userId
     * @return void
     */
    public function setUserId($userId)
    {
        $this->offsetSet('userId', $userId);
        // @todo let session middleware update session if available?
        //$session = $this->getSession();
        //if (!empty($session)) {
        //    $session->setUserId($userId);
        //}
    }

    /**
     * Get current status (if any)
     * @return int|null
     */
    public function getStatus()
    {
        if (!$this->offsetExists('status')) {
            return null;
        }
        return $this->offsetGet('status');
    }

    /**
     * Set current status
     * @param int $status
     * @return void
     */
    public function setStatus($status)
    {
        $this->offsetSet('status', $status);
    }
}
