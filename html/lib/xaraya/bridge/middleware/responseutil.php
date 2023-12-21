<?php
/**
 * @package core\bridge
 * @subpackage middleware
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;

/**
 * Response utilities for PSR-7 and PSR-15 compatible middleware controllers
 */
class ResponseUtil implements DefaultResponseInterface
{
    use DefaultResponseTrait;

    /**
     * Initialize the middleware with response factory (or container, ...)
     * @param array<mixed> $options
     */
    public function __construct(?ResponseFactoryInterface $responseFactory = null, array $options = [])
    {
        $this->setResponseFactory($responseFactory);
        $this->options = $options;
    }
}
