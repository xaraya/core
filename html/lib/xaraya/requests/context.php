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

use Xaraya\Requests\RequestInterface;
use sys;
use RuntimeException;

sys::import('xaraya.server');
sys::import('xaraya.context.contexttrait');

/**
 * Request instance with context for use with xarServer::setInstance() etc.
 */
class RequestContext implements ContextInterface, RequestInterface
{
    use ContextTrait;

    public static string $cookieName = 'XARAYASID';
    public static string $remoteUser = 'REMOTE_USER';
    public static string $authToken = 'HTTP_X_AUTH_TOKEN';

    /** @var array<string, mixed> */
    private array $args = [];

    /**
     * Constructor for the request handler
     * @param array<string, mixed> $args not by reference anymore
     * @param ?Context<string, mixed> $context
     * @return void
     **/
    public function __construct($args = [], $context = null)
    {
        $this->args = $args;
        $this->context = $context;
    }

    /**
     * Initialize the request after setup
     * @return bool
     */
    public function initialize()
    {
        return true;
    }

    /**
     * Summary of getContext
     * @see \BaseActionController::run()
     */
    public function getContext()
    {
        if (!isset($this->context)) {
            throw new RuntimeException('Request context is not initialized yet');
        }
        return $this->context;
    }

    /**
     * Get current requestId from context
     * @return string|null
     * @see \Xaraya\Context\ContextFactory::makeRequestId()
     */
    public function getRequestId()
    {
        return $this->context->getRequestId();
    }

    /**
     * Gets a server variable
     * @param string $name the name of the variable
     * @return mixed value of the variable
     */
    public function getServerVar($name)
    {
        if (!$this->getContext()->offsetExists('server')) {
            return null;
        }
        $serverVars = $this->context->offsetGet('server');
        return $serverVars[$name] ?? null;
    }

    /**
     * Allow setting server variable if needed
     * @param string $name the name of the variable
     * @param mixed $value value of the variable
     * @return void
     */
    public function setServerVar($name, $value)
    {
        if (!$this->getContext()->offsetExists('server')) {
            $this->context['server'] = [];
        }
        $this->context['server'][$name] = $value;
    }

    /**
     * Gets a query variable
     * @param string $name the name of the variable
     * @return mixed value of the variable
     */
    public function getQueryVar($name)
    {
        if (!$this->getContext()->offsetExists('query')) {
            return null;
        }
        $queryVars = $this->context->offsetGet('query');
        return $queryVars[$name] ?? null;
    }

    /**
     * Gets a body variable
     * @param string $name the name of the variable
     * @return mixed value of the variable
     */
    public function getBodyVar($name)
    {
        if (!$this->context->offsetExists('body')) {
            return null;
        }
        $bodyVars = $this->context->offsetGet('body');
        return $bodyVars[$name] ?? null;
    }

    /**
     * Gets input body as JSON object or array
     * @return mixed
     */
    public function getJsonBody()
    {
        if (!$this->context->offsetExists('input')) {
            return null;
        }
        $rawInput = $this->context->offsetGet('input');
        $input = null;
        if (!empty($rawInput)) {
            $input = json_decode($rawInput, true, 512, JSON_THROW_ON_ERROR);
        }
        return $input;
    }

    /**
     * Gets a cookie variable
     * @param string $name the name of the variable
     * @return mixed value of the variable
     */
    public function getCookieVar($name)
    {
        if (!$this->context->offsetExists('cookie')) {
            return null;
        }
        $cookieVars = $this->context->offsetGet('cookie');
        return $cookieVars[$name] ?? null;
    }

    /**
     * Gets all server variables
     * @return array<string, mixed>
     */
    public function getServerParams()
    {
        if (!$this->context->offsetExists('server')) {
            return [];
        }
        return $this->context->offsetGet('server');
    }

    /**
     * Gets all query variables
     * @return array<string, mixed>
     */
    public function getQueryParams()
    {
        if (!$this->context->offsetExists('query')) {
            return [];
        }
        return $this->context->offsetGet('query');
    }

    /**
     * Add all the params we have to the GET array in case they needed to be called in a standard way. e.g. xarVar::fetch
     * @param array<string, mixed> $args
     * @return void
     */
    public function withQueryParams($args)
    {
        if (!$this->context->offsetExists('query')) {
            $this->context['query'] = [];
        }
        $this->context['query'] = $this->context['query'] + $args;
    }

    /**
     * Gets all body variables
     * @return array<string, mixed>
     */
    public function getParsedBody()
    {
        if (!$this->context->offsetExists('body')) {
            return [];
        }
        return $this->context->offsetGet('body');
    }

    /**
     * Gets the raw body input
     * @return string|bool
     */
    public function getRawInput()
    {
        if (!$this->context->offsetExists('input')) {
            return false;
        }
        return $this->context->offsetGet('input');
    }

    /**
     * Gets all cookie variables
     * @return array<string, mixed>
     */
    public function getCookieParams()
    {
        if (!$this->context->offsetExists('cookie')) {
            return [];
        }
        return $this->context->offsetGet('cookie');
    }

    /**
     * Summary of getRemoteUser
     * @param Context<string, mixed> $context
     * @return string
     */
    public static function getRemoteUser($context): string
    {
        if (empty(static::$remoteUser)) {
            return '';
        }
        $serverVars = $context['server'] ?? null;
        if (empty($serverVars) || empty($serverVars[static::$remoteUser])) {
            return '';
        }
        $context['authMethod'] = str_replace(__NAMESPACE__ . '\\', '', __METHOD__);
        return $serverVars[static::$remoteUser];
    }

    /**
     * Summary of getAuthToken
     * @param Context<string, mixed> $context
     * @return string
     */
    public static function getAuthToken($context): string
    {
        if (empty(static::$authToken)) {
            return '';
        }
        $serverVars = $context['server'] ?? null;
        if (empty($serverVars) || empty($serverVars[static::$authToken])) {
            return '';
        }
        $context['authMethod'] = str_replace(__NAMESPACE__ . '\\', '', __METHOD__);
        return $serverVars[static::$authToken];
    }

    /**
     * Summary of getSessionCookie
     * @param Context<string, mixed> $context
     * @return string
     */
    public static function getSessionCookie($context)
    {
        if (empty(static::$cookieName)) {
            return '';
        }
        $cookieVars = $context['cookie'] ?? null;
        if (empty($cookieVars) || empty($cookieVars[static::$cookieName])) {
            return '';
        }
        $context['authMethod'] = str_replace(__NAMESPACE__ . '\\', '', __METHOD__);
        return $cookieVars[static::$cookieName];
    }
}
