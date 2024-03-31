<?php
/**
 * Dummy object with context for use in Twig functions
 */

namespace Xaraya\Bridge\TemplateEngine;

use Xaraya\Core\Traits\ContextInterface;
use Xaraya\Core\Traits\ContextTrait;

/**
 * Dummy object with context for use in Twig functions
 */
class DummyObject implements ContextInterface
{
    use ContextTrait;

    public function __construct($context = null)
    {
        $this->setContext($context);
    }
}
