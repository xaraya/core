<?php
/**
 * Response utilities for PSR-7 and PSR-15 compatible middleware controllers
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
