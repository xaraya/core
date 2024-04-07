<?php
/**
 * Twig extension to use Twig template engine for output in Xaraya
 */

namespace Xaraya\Bridge\TemplateEngine;

use Twig\Extension\AbstractExtension;
use Xaraya\Core\Traits\ContextInterface;
use Xaraya\Core\Traits\ContextTrait;
use Xaraya\Context\Context;

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
 * @uses \sys::autoload()
 */
class XarayaTwigExtension extends AbstractExtension implements ContextInterface
{
    use ContextTrait;

    /**
     * @param ?Context<string, mixed> $context
     */
    public function __construct(?Context $context = null)
    {
        $this->setContext($context);
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
