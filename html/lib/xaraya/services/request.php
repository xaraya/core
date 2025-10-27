<?php

/**
 * Request available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use xarController;
use xarRequest;
use xarServer;
use sys;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via RequestTrait
 */
interface RequestInterface extends ServiceInterface
{
    public const SLICE = 'request2';

    public function getModule(): string;
    public function getType(): string;
    public function getFunction(): string;
    /** @param array<string, mixed> $args */
    public function getCurrentURL(array $args = [], ?bool $generateXMLURL = null): string;
    public function getBaseURI(): string;
    public function getServerVar(string $varName): mixed;
    public function setServerVar(string $name, mixed $value): void;
    public function getVar(string $varName, ?string $allowOnlyMethod = null): mixed;
    public function getMethod(): string;
    public function isLocalReferer(): bool;
    public function isSameReferer(): bool;
    /** @return xarRequest */
    public function getRequest(): xarRequest;
    /** @param array<mixed>|object $var */
    public function getArrayVar(mixed $var, string $name): mixed;
}

/**
 * Request available via methods
 */
trait RequestTrait
{
    use ServiceTrait;

    /**
     * Get the module that was resolved by the router for the current request.
     */
    public function getModule(): string
    {
        return xarController::getRequest()->getModule();
    }

    /**
     * Get the request type (e.g., 'user', 'admin').
     */
    public function getType(): string
    {
        return xarController::getRequest()->getType();
    }

    /**
     * Get the function name for the current request.
     */
    public function getFunction(): string
    {
        return xarController::getRequest()->getFunction();
    }

    /**
     * Get current url
     * @param array<string, mixed> $args
     */
    public function getCurrentURL(array $args = [], ?bool $generateXMLURL = null): string
    {
        return xarServer::getCurrentURL($args, $generateXMLURL);
    }

    /**
     * Get base uri
     */
    public function getBaseURI(): string
    {
        return xarServer::getBaseURI();
    }

    /**
     * Get a server variable
     * @return mixed
     */
    public function getServerVar(string $varName): mixed
    {
        return xarServer::getVar($varName);
    }

    public function setServerVar(string $name, mixed $value): void
    {
        xarServer::setVar($name, $value);
    }

    /**
     * Get a request variable
     * @return mixed
     */
    public function getVar(string $varName, ?string $allowOnlyMethod = null): mixed
    {
        return xarController::getVar($varName, $allowOnlyMethod);
    }

    /**
     * Get the request method (GET, POST, etc.)
     */
    public function getMethod(): string
    {
        return xarServer::getVar('REQUEST_METHOD') ?? 'GET';
    }

    /**
     * Check to see if this is a local referral
     */
    public function isLocalReferer(): bool
    {
        return xarController::isLocalReferer();
    }

    /**
     * Check if the referral comes from the same module
     */
    public function isSameReferer(): bool
    {
        return xarController::isRefererSameModule();
    }

    /**
     * Get current request object
     * @return xarRequest
     */
    public function getRequest(): xarRequest
    {
        return xarController::getRequest();
    }

    /**
     * Handle multi-dimensional array lookup name[key1][key2][...]
     * @param array<mixed>|object $var
     * @return mixed
     */
    public function getArrayVar(mixed $var, string $name): mixed
    {
        return xarController::getArrayVar($var, $name);
    }
}

/**
 * Access xarServer, xarController and xarRequest methods related to the incoming request
 */
class RequestService implements RequestInterface
{
    use RequestTrait;
}