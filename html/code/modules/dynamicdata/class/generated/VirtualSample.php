<?php

namespace Xaraya\DataObject\Generated;

use DataObject;
use DataObjectList;
use VirtualDescriptorTrait;

/**
 * Virtual Sample class exported from DD DataObject configuration
 * with virtual object descriptor loaded from sample-def.php file
 */
class VirtualSample extends DataObject
{
    use VirtualDescriptorTrait;
    protected static string $configFile = 'sample-def.php';

    /**
     * Constructor for VirtualSample
     * @param array<string, mixed> $params
     * @param mixed $context optional context for the DataObject (default = none)
     */
    public function __construct(array $params = [], $context = null)
    {
        $descriptor = $this->getVirtualDescriptor($params, $context);
        parent::__construct($descriptor);
    }
}

/**
 * Virtual Sample class exported from DD DataObject configuration
 * with virtual object descriptor loaded from sample-def.php file
 */
class VirtualSampleList extends DataObjectList
{
    use VirtualDescriptorTrait;
    protected static string $configFile = 'sample-def.php';

    /**
     * Constructor for VirtualSampleList
     * @param array<string, mixed> $params
     * @param mixed $context optional context for the DataObject (default = none)
     */
    public function __construct(array $params = [], $context = null)
    {
        $descriptor = $this->getVirtualDescriptor($params, $context);
        parent::__construct($descriptor);
    }
}
