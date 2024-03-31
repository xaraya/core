<?php
/**
 * Twig extension to use Twig template engine for output in Xaraya
 */

namespace Xaraya\Bridge\TemplateEngine;

use Twig\TwigFunction;
use xarMod;

/**
 * Other Module Tags
 */
class ModuleTagExtension extends XarayaTwigExtension
{
    public function getFilters()
    {
        return [
        ];
    }

    public function getTests()
    {
        return [
        ];
    }

    public function getFunctions()
    {
        return [
            // @todo add other module tags as needed

            /**
             * Workflow tags
             */
            // <xar:workflow-actions name="actions" config="$config" item="$item" title="$item['marking']" template="$item['marking']"/>
            // @todo replace array with fixed order of params
            new TwigFunction('xar_workflow_actions', [$this, 'xar_workflow_actions'], ['is_safe' => ['html']]),
        ];
    }

    // @todo add other module tags as needed

    /**
     * Workflow tags
     */
    public function xar_workflow_actions($args = [])
    {
        return xarMod::apiFunc('workflow', 'user', 'showactions', $args, $this->context);
    }
}
