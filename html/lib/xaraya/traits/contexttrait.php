<?php
/**
 * Trait to add context in other classes
 *
 * Usage:
 * ```
 * use Xaraya\Core\Traits\ContextInterface;
 * use Xaraya\Core\Traits\ContextTrait;
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
 * @package core\traits
 * @subpackage traits
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Core\Traits;

use Xaraya\Context\Context;
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
     * Clear context after cloning
     * @return void
     */
    public function __clone()
    {
        $this->context = null;
    }
}
