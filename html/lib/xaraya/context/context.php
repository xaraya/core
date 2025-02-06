<?php
/**
 * @package core\context
 * @subpackage context
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
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

/**
 * Context object for request etc.
 * @template TKey of array-key
 * @template TValue of mixed
 * @extends ArrayObject<TKey, TValue>
 */
class Context extends ArrayObject implements ContextObjectInterface
{
    protected bool $enableTrace = false;
    protected float $startTrace = 0;
    /** @var array<mixed> */
    protected array $tracePaths = [];

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
            sys::import('xaraya.context.user');
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
        //sys::import('xaraya.sessions.context');
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

    /**
     * Set current response
     * @param array<string, mixed> $headers
     * @return void
     */
    public function setResponse(?string $output = null, int $status = 200, string $mediaType = '', array $headers = [])
    {
        $this->setStatus($status);
        // @todo see also xarResponse
        $response = [
            'status' => $status,
            'output' => $output,
            'mediaType' => $mediaType,
            'headers' => $headers,
        ];
        $this->offsetSet('response', $response);
    }

    /**
     * Get or set enableTrace
     */
    public function enableTrace(?bool $enable = null): bool
    {
        if (isset($enable)) {
            $this->enableTrace = $enable;
            $this->startTrace = microtime(true);
            $this->tracePaths = [];
            $this->tracePaths[] = [$this->startTrace, 'start trace', null];
        }
        return $this->enableTrace;
    }

    /**
     * Summary of tracePath
     * @param string $message
     * @param mixed $infoPath
     * @return void
     */
    public function tracePath($message, $infoPath = null)
    {
        if (!$this->enableTrace) {
            return;
        }
        $elapsed = sprintf('%.3f', (microtime(true) - $this->startTrace) * 1000.0);
        $this->tracePaths[] = [$elapsed, $message, $infoPath];
    }

    /**
     * Summary of getTrace
     * @return array<mixed>
     */
    public function getTrace()
    {
        $this->tracePath('stop trace');
        return $this->tracePaths;
    }

    /**
     * Avoid issues with serialize, cfr. pager blockOptions with context
     * In fact, since the context is for a particular request, drop it altogether
     * @internal
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        //$vars = $this->getArrayCopy();
        //return array_diff_key($vars, ['twig' => false]);
        return ['source' => __METHOD__];
    }

    /**
     * Fill the context with the unserialized data
     * @internal
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $this->exchangeArray($data);
        // or fill it again from current request or globals
    }
}
