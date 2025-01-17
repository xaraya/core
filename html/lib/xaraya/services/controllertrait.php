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
use xarServer;
use DataObject;
use DataObjectList;
use sys;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via ControllerTrait
 */
interface ControllerInterface extends ServiceInterface
{
    /**
     * Get url for this module type function
     * @param array<string, mixed> $args
     */
    public function getURL(string $modType = 'user', string $funcName = 'main', array $args = []): string;

    /**
     * Get url for that object method
     * @param array<string, mixed> $args
     */
    public function getObjectURL(?string $objectName = null, string $methodName = 'view', array $args = []): string;

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
 * @template TParent of ServicesInterface
 */
trait ControllerTrait
{
    /** @use ServiceTrait<TParent> */
    use ServiceTrait;

    /**
     * Get url for this module type function
     * @param array<string, mixed> $args
     */
    public function getURL(string $modType = 'user', string $funcName = 'main', array $args = []): string
    {
        return xarController::URL($this->getModName(), $modType, $funcName, $args);
    }

    /**
     * Get url for that object method
     * @param array<string, mixed> $args
     */
    public function getObjectURL(?string $objectName = null, string $methodName = 'view', array $args = []): string
    {
        $objectName ??= $this->getObject()?->name;
        return xarServer::getObjectURL($objectName, $methodName, $args);
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
 * Access xarController::* Main Controller methods (getURL, redirect, ...)
 *
 * Available methods:
 * - getURL()
 * - redirect()
 * - forbidden()
 * - notFound()
 * - badRequest()
 * - getObjectURL()
 * - ...
 *
 * Required methods in parent:
 * - getModName() for ctl()->getURL()
 * - getObject() for ctl()->getObjectURL()
 *
 * @template TParent of ServicesInterface
 */
class ControllerService implements ControllerInterface
{
    /** @use ControllerTrait<TParent> */
    use ControllerTrait;

    /**
     * Get name of the module from parent
     */
    public function getModName(): string
    {
        return $this->getParent()->getModName();
    }

    /**
     * Get data object or objectlist from parent
     */
    public function getObject(): DataObjectList|DataObject|null
    {
        return $this->getParent()->getObject();
    }
}
