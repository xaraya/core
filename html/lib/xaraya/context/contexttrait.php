<?php

/**
 * Trait to add context in other classes
 *
 * Usage:
 * ```
 * use Xaraya\Context\WithContextInterface;
 * use Xaraya\Context\WithContextTrait;
 *
 * class myFancyClass implements WithContextInterface
 * {
 *     use WithContextTrait;
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

/**
 * For documentation purposes only - available via WithContextTrait
 */
interface WithContextInterface
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
 * @deprecated 2.9.3 use WithContextInterface() instead
 */
interface ContextInterface extends WithContextInterface
{
    // ...
}

/**
 * Summary of WithContextTrait
 */
trait WithContextTrait
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
        // @todo check context in concurrent environment
        // $this->context = null;
    }
}

/**
 * @deprecated 2.9.3 use WithContextTrait() instead
 */
trait ContextTrait
{
    use WithContextTrait;
}
