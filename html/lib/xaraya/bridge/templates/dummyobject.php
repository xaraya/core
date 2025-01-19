<?php
/**
 * Dummy object with context for use in Twig functions
 */

namespace Xaraya\Bridge\TemplateEngine;

use VirtualObjectDescriptor;
use DataObject;

/**
 * Dummy object with context for use in Twig functions
 */
/**
class DummyObject implements ContextInterface
{
    use ContextTrait;

    public function __construct($context = null)
    {
        $this->setContext($context);
    }
}
 */

class DummyObjectFactory
{
    /** @var ?DataObject */
    protected static $instance = null;

    public static function getDummyObject($context = null)
    {
        if (!isset(static::$instance)) {
            $descriptor = new VirtualObjectDescriptor(['name' => 'dummy']);
            static::$instance = new DataObject($descriptor);
        }
        $object = clone static::$instance;
        $object->setContext($context);
        return $object;
    }
}
