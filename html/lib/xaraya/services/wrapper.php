<?php

/**
 * Wrapper available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

/**
 * For documentation purposes only - available via WrapperTrait
 */
interface WrapperInterface extends ServiceInterface
{
    public const SLICE = 'wrapper';

    public function __call($method, $args);
}

/**
 * Wrapper available via methods
 */
trait WrapperTrait
{
    use ServiceTrait;

    /** @var callable */
    public $callable;
    /** @var class-string */
    public $className;

    /**
     * Create service class for parent with callable
     */
    public function __construct(mixed $parent, ?callable $callable = null, ?string $className = null)
    {
        $this->parent = $parent;
        $this->callable = $callable;
        $this->className = $className;
    }

    /**
     * Initialize service class
     * @param array<string, mixed> $config
     */
    public function init(array $config = []): bool
    {
        if (!empty($this->className) && !method_exists($this->className, 'init')) {
            return true;
        }
        // override default ServiceInterface here + pass along $xar
        return $this->__call('init', [$config, $this->getParent()]);
    }

    /**
     * Get configuration
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        if (!empty($this->className) && !method_exists($this->className, 'getConfig')) {
            return [];
        }
        // override default ServiceInterface here
        return $this->__call('getConfig', []);
    }

    /**
     * Magic call method with callable
     */
    public function __call($method, $args)
    {
        $callable = $this->callable;
        return $callable($method, ...$args);
    }

    public function __serialize()
    {
        $data = xar::getPublicProperties($this);
        // Serialization of 'Closure' is not allowed
        unset($data['callable']);
        return $data;
    }

    public function __unserialize($data)
    {
        foreach ($data as $name => $value) {
            $this->{$name} = $value;
        }
        // Reconnect to current static services
        $this->parent = xar::getServicesClass();
        // Re-create callable based on className (static only)
        $this->callable = function ($method, ...$args) {
            return $this->className::$method(...$args);
        };
    }

    /**
     * Summary of create
     * @param ?class-string $className
     * @param ?object $instance
     */
    public static function create(mixed $parent, $className, &$instance): static
    {
        if (!is_object($parent)) {
            $parent = new DummyParent($parent);
        }
        // use callable - we can't create anonymous class and change static property here
        if (isset($instance)) {
            $callable = function ($method, ...$args) use (&$instance) {
                return $instance->$method(...$args);
            };
            return new static($parent, $callable, $instance::class);
        }
        $callable = function ($method, ...$args) use ($className) {
            return $className::$method(...$args);
        };
        return new static($parent, $callable, $className);
    }
}

/**
 * Access *::* any class methods as $this->*()->* instance methods
 *
 * Available methods:
 * - $this->prep()->text()
 * - $this->events()->notify()
 * - ...
 *
 */
class WrapperService implements WrapperInterface
{
    use WrapperTrait;
}
