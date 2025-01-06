<?php
/**
 * Dummy object with context for use in Twig functions
 */

namespace Xaraya\Bridge\TemplateEngine;

use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;

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
