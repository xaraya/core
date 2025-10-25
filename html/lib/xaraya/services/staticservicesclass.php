<?php

/**
 * Core Services for static classes (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Services;

use Xaraya\Context\Context;
use Xaraya\Requests\RequestInterface;
use Xaraya\Sessions\SessionInterface;

/**
 * Core Services for static classes (WIP)
 *
 * This holds the context, request and session in storage
 * for each request in normal static, reactphp fiber and
 * swoole coroutine environment - see WithStaticServices
 */
class StaticServicesClass extends ServicesClass
{
    public const SLICE = 'static';

    /** @var ?RequestInterface */
    protected $requestInstance = null;
    /** @var ?SessionInterface */
    protected $sessionInstance = null;
    /** @var array<string, ServiceInterface> */
    public array $serviceCache = [];

    /**
     * @return Context<string, mixed>|null
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param ?Context<string, mixed> $context
     * @return void
     */
    public function setContext($context)
    {
        $this->context = $context;
        // reset request & session instance if context is replaced
        $this->requestInstance = null;
        $this->sessionInstance = null;
    }

    /**
     * @return RequestInterface|null
     */
    public function getRequestInstance()
    {
        if (!isset($this->requestInstance)) {
            // do *not* initialize request here - depends on the caller
            //$this->requestInstance ??= xarServer::newInstance($this->context);
        }
        return $this->requestInstance;
    }

    /**
     * @param ?RequestInterface $instance
     * @return void
     * @see xarServer::setInstance()
     */
    public function setRequestInstance($instance)
    {
        $this->requestInstance = $instance;
    }

    /**
     * @return SessionInterface|null
     */
    public function getSessionInstance()
    {
        if (!isset($this->sessionInstance)) {
            // do *not* initialize session here - depends on the caller
            //$this->sessionInstance ??= xarSession::newInstance($this->context);
        }
        return $this->sessionInstance;
    }

    /**
     * @param ?SessionInterface $instance
     * @return void
     * @see xarSession::setInstance()
     */
    public function setSessionInstance($instance)
    {
        $this->sessionInstance = $instance;
    }

    /**
     * Get a service prototype instance, creating it if not already cached.
     * This ensures only one prototype per service type per request.
     *
     * @param string $name The name of the service.
     * @return ServiceInterface The service prototype.
     */
    public function getServicePrototype(string $name): ServiceInterface
    {
        if (!isset($this->serviceCache[$name])) {
            // For prototypes, the parent is the static services class itself.
            $this->serviceCache[$name] = ServiceFactory::createServicePrototype($name, $this);
        }
        return $this->serviceCache[$name];
    }
}
