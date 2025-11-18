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
use Xaraya\Bridge\GraphQL\GraphQLHandler;
use Xaraya\Bridge\RestAPI\RestAPIHandler;
use Xaraya\Bridge\Requests\BridgeRequest;

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
    // Used in GraphQL field resolvers - only needed in RestAPI to disable cache
    /** @var GraphQLHandler|RestAPIHandler|BridgeRequest|null */
    public mixed $handler = null;

    /**
    public function __construct(array|object $array = [], int $flags = 0, string $iteratorClass = \ArrayIterator::class) {
        parent::__construct($array, $flags, $iteratorClass);
        if (!empty($array['source'])) {
            echo "New context from " . $array['source'] . ":<br>\n";
            debug_print_backtrace();
            echo "<br>\n";
        } else {
            echo "New context from (?): ";
            debug_print_backtrace();
            echo "<br>\n";
        }
    }
     */

    /**
     * Get current requestId
     * @return string|null
     * @see \Xaraya\Context\ContextFactory::makeRequestId()
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
     * @see \Xaraya\Context\SessionContext::startSession()
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
     * @see \Xaraya\Context\UserContext::getUserId()
     */
    public function getUserId($xar = null)
    {
        if (!$this->offsetExists('userId')) {
            $userContext = new UserContext($this, $xar);
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

    public function getSliceValue(string $slice, string $name): mixed
    {
        if (!$this->offsetExists($slice)) {
            $this->offsetSet($slice, []);
            return null;
        }
        return $this->offsetGet($slice)[$name] ?? null;
    }

    public function setSliceValue(string $slice, string $name, mixed $value): void
    {
        if (!$this->offsetExists($slice)) {
            $this->offsetSet($slice, []);
        }
        // update actual slice array here
        $this[$slice][$name] = $value;
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
