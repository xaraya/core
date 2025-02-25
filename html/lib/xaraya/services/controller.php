<?php

/**
 * Controller available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
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
use xarDDObject;
use sys;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via ControllerTrait
 */
interface ControllerInterface extends ServiceInterface
{
    /**
     * Get url for some module type function
     * @param array<string, mixed> $args
     */
    public function getModuleURL(?string $modName = null, string $modType = 'user', string $funcName = 'main', array $args = [], ?bool $generateXMLURL = null): string;

    /**
     * Get url for some object method
     * @param array<string, mixed> $args
     */
    public function getObjectURL(?string $objectName = null, string $methodName = 'view', array $args = [], ?bool $generateXMLURL = null): string;

    /**
     * Generate URL for a specific action on an object - the format will depend on the linktype
     * @param array<string, mixed> $extra extra arguments to pass to the URL
     */
    public function getActionURL(object $object, string $action = '', mixed $itemid = null, array $extra = []): string;

    /**
     * Get current url
     * @param array<string, mixed> $args
     */
    public function getCurrentURL(array $args = [], ?bool $generateXMLURL = null): string;

    /**
     * Get base url
     */
    public function getBaseURL(): string;

    /**
     * Get base uri
     */
    public function getBaseURI(): string;

    public function getServerVar(string $varName): mixed;

    /**
     * Get current request
     * @return xarRequest
     */
    public function getRequest(): xarRequest;

    public function getRequestVar(string $varName, ?string $allowOnlyMethod = null): mixed;

    public function getRequestMethod(): string;

    public function isSameReferer(): bool;

    /**
     * Send redirect to url and exit
     * @return bool|never
     */
    public function redirect(string $url, ?int $httpResponse = null);

    /**
     * Return a 403 Forbidden header and fill in the 'message-forbidden.xt' template
     * @return string output display string
     */
    public function forbidden(string $msg = '', ?string $template = null): string;

    /**
     * Return a 404 Not Found header and fill in the 'message-notfound.xt' template
     * @return string output display string
     */
    public function notFound(string $msg = '', ?string $template = null): string;

    /**
     * Return a 400 Bad Request header and fill in the 'user-errors.xt' template with optional layout
     * @return string output display string
     */
    public function badRequest(?string $layout = null): string;
}

/**
 * Controller available via methods
 */
trait ControllerTrait
{
    use ServiceTrait;

    /**
     * Get url for a module type function
     * @param array<string, mixed> $args
     */
    public function getModuleURL(?string $modName = null, string $modType = 'user', string $funcName = 'main', array $args = [], ?bool $generateXMLURL = null): string
    {
        return xarController::URL($modName, $modType, $funcName, $args, $generateXMLURL);
    }

    /**
     * Get url for an object method
     * @param array<string, mixed> $args
     */
    public function getObjectURL(?string $objectName = null, string $methodName = 'view', array $args = [], ?bool $generateXMLURL = null): string
    {
        return xarServer::getObjectURL($objectName, $methodName, $args, $generateXMLURL);
    }

    /**
     * Generate URL for a specific action on an object - the format will depend on the linktype
     *
     * @param object $object the object or object list we want to create an URL for
     * @param string $action the action we want to take on this object (= method or func)
     * @param mixed $itemid the specific item id or null
     * @param array<string, mixed> $extra extra arguments to pass to the URL - CHECKME: we should only need itemid here !?
     * @return string the generated URL
     */
    public function getActionURL(object $object, string $action = '', mixed $itemid = null, array $extra = []): string
    {
        return xarDDObject::getActionURL($object, $action, $itemid, $extra);
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
     * Get base url
     */
    public function getBaseURL(): string
    {
        return xarServer::getBaseURL();
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

    /**
     * Get current request
     * @return xarRequest
     */
    public function getRequest(): xarRequest
    {
        return xarController::getRequest();
    }

    /**
     * Get a request variable
     * @return mixed
     */
    public function getRequestVar(string $varName, ?string $allowOnlyMethod = null): mixed
    {
        return xarController::getVar($varName, $allowOnlyMethod);
    }

    public function getRequestMethod(): string
    {
        return xarServer::getVar('REQUEST_METHOD') ?? 'GET';
    }

    public function isSameReferer(): bool
    {
        return xarController::isRefererSameModule();
    }

    /**
     * Send redirect to url and exit
     * @uses xarController::redirect()
     * @return bool|never
     */
    public function redirect(string $url, ?int $httpResponse = null)
    {
        return xarController::redirect($url, $httpResponse, $this->getContext());
    }

    /**
     * Return a 403 Forbidden header and fill in the 'message-forbidden.xt' template
     * @return string output display string
     */
    public function forbidden(string $msg = '', ?string $template = null): string
    {
        return xarController::forbidden($msg, $this->getContext(), $template);
    }

    /**
     * Return a 404 Not Found header and fill in the 'message-notfound.xt' template
     * @return string output display string
     */
    public function notFound(string $msg = '', ?string $template = null): string
    {
        return xarController::notFound($msg, $this->getContext(), $template);
    }

    /**
     * Return a 400 Bad Request header and fill in the 'user-errors.xt' template with optional layout
     * @return string output display string
     */
    public function badRequest(?string $layout = null): string
    {
        return xarController::badRequest($layout, $this->getContext());
    }
}

/**
 * Access xarController::* Main Controller methods (URL, redirect, ...)
 *
 * Available methods:
 * - getModuleURL() - or use mod()->getURL() for current module
 * - getObjectURL() - or use data()->getURL() for current object
 * - getActionURL() - or use $object->getActionURL() with actual object
 * - getCurrentURL()
 * - getBaseURL()
 * - getBaseURI()
 * - getServerVar()
 * - getRequest()
 * - getRequestVar()
 * - getRequestMethod()
 * - isSameReferer()
 * - redirect()
 * - forbidden()
 * - notFound()
 * - badRequest()
 * - ...
 *
 */
class ControllerService implements ControllerInterface
{
    use ControllerTrait;
}
