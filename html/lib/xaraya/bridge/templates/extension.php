<?php

/**
 * Twig extension to use Twig template engine for output in Xaraya
 */

namespace Xaraya\Bridge\TemplateEngine;

use Twig\Extension\AbstractExtension;
use Xaraya\Context\Context;
use Xaraya\Services\ServicesInterface;
use Xaraya\Services\ServicesTrait;

/**
 * Use Twig template engine to generate output in Xaraya
 *
 * Xaraya Extensions:
 * 1. XarayaCoreExtension - see xaraya.php
 * 2. BlocklayoutTagExtension - see blocklayout.php
 * 3. DynamicDataTagExtension - see dynamicdata.php
 * 4. ModuleTagExtension - see modules.php
 * 5. PHPOtherExtension - see phpothers.php
 *
 */
class XarayaTwigExtension extends AbstractExtension implements ServicesInterface
{
    use ServicesTrait;

    public string $moduleName;
    public string $moduleType;
    public int $itemtype = 0;
    /** @var \DataObject|\DataObjectList|null */
    public $object;
    /** @var \DataProperty|null */
    public $property;

    /**
     * @param ?Context<string, mixed> $context
     */
    public function __construct(?Context $context = null)
    {
        $this->setContext($context);
        // initialize modname etc. based on context or request
        $this->moduleName = $context['module'] ?? $this->req()->getRequest()->getModule();
        $this->moduleType = $context['modtype'] ?? $this->req()->getRequest()->getType();
        $this->itemtype = $context['itemtype'] ?? 0;
        //$this->object = $context['object'] ?? null;
    }

    public function getFilters()
    {
        return [];
    }

    public function getTests()
    {
        return [];
    }

    public function getFunctions()
    {
        return [];
    }
}
