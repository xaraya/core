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

use Xaraya\Core\Traits\ContextInterface;
use Xaraya\Core\Traits\ContextTrait;
use Xaraya\Requests\RequestInterface;
use xarServer;
use sys;

sys::import('xaraya.server');
sys::import('xaraya.traits.contexttrait');

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
     * @uses xarServer::setInstance()
     * @return void
     **/
    public function __construct($args)
    {
        $this->args = $args;
        xarServer::setInstance($this);
    }

    /**
     * Initialize the request after setup
     * @return bool
     */
    public function initialize()
    {
        if (!isset($this->context)) {
            $this->context = new Context();
            //$requestId = bin2hex(random_bytes($this->length));
        }
        return true;
    }

    /**
     * Get current requestId from context
     * @return string|null
     */
    public function getRequestId()
    {
        return $this->getContext()->getRequestId();
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
        $serverVars = $this->getContext()->offsetGet('server');
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
        return;
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
        $queryVars = $this->getContext()->offsetGet('query');
        return $queryVars[$name] ?? null;
    }

    /**
     * Gets a body variable
     * @param string $name the name of the variable
     * @return mixed value of the variable
     */
    public function getBodyVar($name)
    {
        if (!$this->getContext()->offsetExists('body')) {
            return null;
        }
        $bodyVars = $this->getContext()->offsetGet('body');
        return $bodyVars[$name] ?? null;
    }

    /**
     * Gets input body as JSON object or array
     * @return mixed
     */
    public function getJsonBody()
    {
        if (!$this->getContext()->offsetExists('input')) {
            return null;
        }
        $rawInput = $this->getContext()->offsetGet('input');
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
        if (!$this->getContext()->offsetExists('cookie')) {
            return null;
        }
        $cookieVars = $this->getContext()->offsetGet('cookie');
        return $cookieVars[$name] ?? null;
    }

    /**
     * Gets all server variables
     * @return array<string, mixed>
     */
    public function getServerParams()
    {
        if (!$this->getContext()->offsetExists('server')) {
            return [];
        }
        return $this->getContext()->offsetGet('server');
    }

    /**
     * Gets all query variables
     * @return array<string, mixed>
     */
    public function getQueryParams()
    {
        if (!$this->getContext()->offsetExists('query')) {
            return [];
        }
        return $this->getContext()->offsetGet('query');
    }

    /**
     * Add all the params we have to the GET array in case they needed to be called in a standard way. e.g. xarVar::fetch
     * @param array<string, mixed> $args
     * @return void
     */
    public function withQueryParams($args)
    {
        if (!$this->getContext()->offsetExists('query')) {
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
        if (!$this->getContext()->offsetExists('body')) {
            return [];
        }
        return $this->getContext()->offsetGet('body');
    }

    /**
     * Gets all cookie variables
     * @return array<string, mixed>
     */
    public function getCookieParams()
    {
        if (!$this->getContext()->offsetExists('cookie')) {
            return [];
        }
        return $this->getContext()->offsetGet('cookie');
    }

    /**
     * Summary of getRemoteUser
     * @param Context<string, mixed> $context
     * @return string
     */
    public static function getRemoteUser($context): string
    {
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
        $cookieVars = $context['cookie'] ?? null;
        if (empty($cookieVars) || empty($cookieVars[static::$cookieName])) {
            return '';
        }
        $context['authMethod'] = str_replace(__NAMESPACE__ . '\\', '', __METHOD__);
        return $cookieVars[static::$cookieName];
    }
}
