<?php

/**
 * Generic handler interface for routing & dispatching outside Xaraya
 *
 * Experiment using module classes and methods as handler
 */

namespace Xaraya\Routing;

use Xaraya\Context\ContextInterface;

/**
 * Generic handler interface for routing & dispatching outside Xaraya
 */
interface HandlerInterface extends ContextInterface
{
    /**
     * Call the right handler after matching the route
     * @param array<string, mixed> $vars
     */
    public function callHandler(mixed $handler, array $vars = []): mixed;

    /**
     * Create output for result
     */
    public function output(mixed $result, mixed $transform = null): string;
}
