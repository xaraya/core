<?php
/**
 * Dummy object with context for use in Twig functions
 */

namespace Xaraya\Bridge\TemplateEngine;

use VirtualObjectDescriptor;
use DataObject;

/**
 * Get dummy object with context for use in Twig functions
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
