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
    public function getModuleURL(string $modName, string $modType = 'user', string $funcName = 'main', array $args = []): string;

    /**
     * Get url for some object method
     * @param array<string, mixed> $args
     */
    public function getObjectURL(string $objectName, string $methodName = 'view', array $args = []): string;

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
    public function getModuleURL(string $modName, string $modType = 'user', string $funcName = 'main', array $args = []): string
    {
        return xarController::URL($modName, $modType, $funcName, $args);
    }

    /**
     * Get url for an object method
     * @param array<string, mixed> $args
     */
    public function getObjectURL(string $objectName, string $methodName = 'view', array $args = []): string
    {
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
 * Access xarController::* Main Controller methods (URL, redirect, ...)
 *
 * Available methods:
 * - URL() - or use mod()->getURL() for current module
 * - getObjectURL() - or use data()->getURL() for current object
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
