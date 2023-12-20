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

use Xaraya\Authentication\Usercontext;
use ArrayObject;
use sys;

sys::import('modules.authsystem.class.usercontext');

interface ContextInterface
{
    /**
     * Get current requestId
     * @return mixed
     */
    public function getRequestId();

    /**
     * Get current session (if any)
     * @return mixed
     */
    public function getSession();

    /**
     * Get current userId
     * @return mixed
     */
    public function getUserId();

    /**
     * Set current userId
     * @param mixed $userId
     * @return void
     */
    public function setUserId($userId);
}

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
     * @return mixed
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
     * Get current userId
     * @return mixed
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
     * @param mixed $userId
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
}

class ContextFactory
{
    /** @var array<string, string> */
    public static array $mapping = [
        'requestId' => 'requestId',
        //'sessionId' => 'sessionId',
        'session' => 'session',
        'userId' => 'userId',
    ];

    /**
     * Create new context from request
     * @param mixed $request PSR-7 server request if available
     * @param mixed $source source where the context is created
     * @return Context<string, mixed>
     */
    public static function fromRequest(&$request = null, $source = null)
    {
        if (!empty($request)) {
            // @todo use static::$mapping of context key to request attribute
            // set context from request attributes
            $context = new Context((array) $request->getAttributes());
            // @todo don't save request in the context for now, unless we really need it later...
            //$context['request'] = &$request;
            $context['requestId'] = static::makeRequestId($request);
            // @todo see rest handler and graphql for getUserId()
            //$context['server'] = $request->getServerParams();
            //$context['cookie'] = $request->getCookieParams();
        } else {
            $context = new Context();
            $context['requestId'] = null;
            // @todo see rest handler and graphql for getUserId()
            //$context['server'] = $_SERVER;
            //$context['cookie'] = $_COOKIE;
        }
        if (!empty($source)) {
            $context['source'] = $source;
        }
        return $context;
    }

    /**
     * Make (or get) requestId from request
     * @param mixed $request PSR-7 server request
     * @return string
     */
    public static function makeRequestId(&$request)
    {
        // @todo use static::$mapping of context key to request attribute
        $requestId = $request->getAttribute('requestId');
        if (empty($requestId)) {
            $requestId = 'req_' . (string) spl_object_id($request) . '-' . (string) microtime(true);
            $request = $request->withAttribute('requestId', $requestId);
        }
        return $requestId;
    }
}
