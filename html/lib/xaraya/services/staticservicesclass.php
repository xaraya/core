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
    /** @var ?RequestInterface */
    protected $requestInstance = null;
    /** @var ?SessionInterface */
    protected $sessionInstance = null;

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
     */
    public function setSessionInstance($instance)
    {
        $this->sessionInstance = $instance;
    }
}
