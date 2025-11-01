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

use sys;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via WrapperTrait
 */
interface WrapperInterface extends ServiceInterface
{
    public const SLICE = 'wrapper';

    public function __call($method, $args);
    public static function __callStatic($method, $args);
}

/**
 * Wrapper available via methods
 */
trait WrapperTrait
{
    use ServiceTrait;

    /**
     * Summary of create
     * @param ?class-string $className
     * @param ?object $instace
     */
    public static function create(mixed $parent, $className, $instance): static
    {
        if (!is_object($parent)) {
            $parent = new DummyParent($parent);
        }
        $wrapper = new class($parent) extends WrapperService {};
        if (isset($instance)) {
            $wrapper::$className = $instance::class;
            $wrapper::$instance = $instance;
        } else {
            $wrapper::$className = $className;
            // @todo do we need to instantiate here
            $wrapper::$instance = new $className();
        }
        return $wrapper;
    }
}

/**
 * Access *::* any class methods
 *
 * Available methods:
 * - ...
 *
 */
class WrapperService implements WrapperInterface
{
    use WrapperTrait;

    /** @var class-string */
    public static $className;
    /** @var object */
    public static $instance;

    public function __call($method, $args)
    {
        $instance = static::$instance;
        return $instance->$method(...$args);
    }

    public static function __callStatic($method, $args)
    {
        $className = static::$className;
        return $className::$method(...$args);
    }
}
