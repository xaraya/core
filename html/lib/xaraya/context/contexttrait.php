<?php

/**
 * Trait to add context in other classes
 *
 * Usage:
 * ```
 * use Xaraya\Context\ContextInterface;
 * use Xaraya\Context\ContextTrait;
 *
 * class myFancyClass implements ContextInterface
 * {
 *     use ContextTrait;
 *
 *     public function doSomething()
 *     {
 *         // ... get current context ...
 *         $context = $this->getContext();
 *
 *         // ... update current context ...
 *         $this->setContext($context);
 *     }
 * }
 * ```
 *
 * @package core\context
 * @subpackage context
 * @category Xaraya Web Applications Framework
 * @version 2.5.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Context;

use sys;

sys::import("xaraya.context.context");

/**
 * For documentation purposes only - available via ContextTrait
 */
interface ContextInterface
{
    /**
     * @return ?Context<string, mixed>
     */
    public function getContext();

    /**
     * @param ?Context<string, mixed> $context
     * @return void
     */
    public function setContext($context);

    /**
     * @return bool
     */
    public function hasContext();

    /**
     * Reset context after cloning
     * @return void
     */
    public function __clone();
}

/**
 * Summary of ContextTrait
 */
trait ContextTrait
{
    /** @var ?Context<string, mixed> */
    protected $context = null;

    /**
     * @return ?Context<string, mixed>
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param ?Context<string, mixed> $context
     * @return void
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return bool
     */
    public function hasContext()
    {
        return isset($this->context);
    }

    /**
     * Clear context after cloning
     * @return void
     */
    public function __clone()
    {
        $this->context = null;
    }
}
