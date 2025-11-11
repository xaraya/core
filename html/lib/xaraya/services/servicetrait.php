<?php

/**
 * Service available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;
use Xaraya\Context\Context;
use sys;

/**
 * @todo find out why sys::import() has an issue with autoload of xarDatabase() in gql.php
 */
if (interface_exists('Xaraya\Services\ServiceInterface', false)) {
    return;
}

/**
 * For documentation purposes only - available via ServiceTrait
 */
interface ServiceInterface extends ContextInterface
{
    public function __construct(mixed $parent);
    /** @param array<string, mixed> $config */
    public function init(array $config = []): bool;
    /** @return array<string, mixed> */
    public function getConfig(): array;
    public function getParent(): mixed;
    public function setParent(mixed $parent): void;
    /**
     * Create a specialized version of this service instance.
     * @param mixed ...$args
     * @return ServiceInterface
     */
    public function specialize(...$args): ServiceInterface;
}

/**
 * Service available via methods
 */
trait ServiceTrait
{
    use ContextTrait;

    public mixed $parent;

    /**
     * Create service class for parent
     */
    public function __construct(mixed $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Initialize service class
     * @param array<string, mixed> $config
     */
    public function init(array $config = []): bool
    {
        if (empty($config)) {
            $config = $this->getConfig();
        }
        $this->getContext()[static::SLICE] ??= $config;
        return true;
    }

    /**
     * Get configuration
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return [];
    }

    /**
     * Get parent of service class
     */
    public function getParent(): mixed
    {
        // @todo this should only be called when parent has services interface
        assert($this->parent instanceof ServicesInterface);
        return $this->parent;
    }

    /**
     * Set parent of service class
     */
    public function setParent(mixed $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Create a specialized version of this service instance.
     * By default, it just returns a clone of itself.
     * @param mixed ...$args
     * @return ServiceInterface
     */
    public function specialize(...$args): ServiceInterface
    {
        return clone $this;
    }

    /**
     * Get context from parent
     * @return ?Context<string, mixed>
     */
    public function getContext()
    {
        return $this->getParent()->getContext();
    }

    public function setContext($context)
    {
        if (!empty($context)) {
            // avoid loops for data() and mod() in request handlers
            if (!$this->getParent()->hasContext()) {
                $this->getParent()->setContext($context);
            }
        }
    }

    public function hasContext()
    {
        return $this->getParent()->hasContext();
    }

    /**
     * Summary of create
     */
    public static function create(mixed $parent): static
    {
        // @todo handle context for facades
        if (!is_object($parent)) {
            $parent = new DummyParent($parent);
        }
        return new static($parent);
    }
}

/**
 * Access xar*::* service methods
 */
class ServiceClass implements ServiceInterface
{
    use ServiceTrait;
}

/**
 * Dummy parent with context for facades
 * @todo handle context for facades
 */
class DummyParent implements ContextInterface
{
    use ContextTrait;

    protected mixed $parent = null;

    public function __construct(mixed $parent = null)
    {
        $this->parent = $parent;
    }

    public function getContext()
    {
        if (!isset($this->context)) {
            // $this->context = new Context(['source' => $this->parent]);
            // Use context from static services class here
            $this->context = xar::getServicesClass()->getContext();
        }
        return $this->context;
    }
}
