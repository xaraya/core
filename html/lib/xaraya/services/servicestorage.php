<?php

/**
 * Service Storage for core service classes (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Services;

/**
 * For documentation purposes only
 */
interface ServiceStorageInterface
{
    public function get(): ?ServicesInterface;
    public function set(ServicesInterface $services): void;
    public function has(): bool;
    public function clear(): void;
    public function count(): int;
}

/**
 * Default static storage for traditional request model
 */
class StaticServiceStorage implements ServiceStorageInterface
{
    /** @var ?ServicesInterface */
    protected static $services = null;

    public function get(): ?ServicesInterface
    {
        return static::$services;
    }
    public function set(ServicesInterface $services): void
    {
        static::$services = $services;
    }
    public function has(): bool
    {
        return isset(static::$services);
    }
    public function clear(): void
    {
        static::$services = null;
    }

    public function count(): int
    {
        return isset(static::$services) ? 1 : 0;
    }
}

if (class_exists('Fiber') && class_exists('WeakMap')) {
    /**
     * Fiber-local storage for concurrent request model (PHP 8.1+)
     */
    class FiberServiceStorage implements ServiceStorageInterface
    {
        /** @var ?\WeakMap<\Fiber, ServicesInterface> */
        protected static $servicesByFiber = null;
        /** @var ?\stdClass */
        protected static $dummyFiber = null;

        public function __construct()
        {
            // Initialize the WeakMap when the first instance is created.
            if (static::$servicesByFiber === null) {
                static::$servicesByFiber = new \WeakMap();
            }
        }

        protected function getCurrentFiber(): \Fiber|\stdClass
        {
            $fiber = \Fiber::getCurrent();
            if ($fiber === null) {
                // Not in a fiber, return a static dummy object to use as a key
                if (static::$dummyFiber === null) {
                    static::$dummyFiber = new \stdClass();
                }
                return static::$dummyFiber;
            }
            return $fiber;
        }

        public function get(): ?ServicesInterface
        {
            $fiber = $this->getCurrentFiber();
            return static::$servicesByFiber[$fiber] ?? null;
        }

        public function set(ServicesInterface $services): void
        {
            $fiber = $this->getCurrentFiber();
            static::$servicesByFiber[$fiber] = $services;
        }

        public function has(): bool
        {
            $fiber = $this->getCurrentFiber();
            return isset(static::$servicesByFiber[$fiber]);
        }

        public function clear(): void
        {
            $fiber = $this->getCurrentFiber();
            unset(static::$servicesByFiber[$fiber]);
        }

        public function count(): int
        {
            return count(static::$servicesByFiber);
        }
    }
}

if (class_exists('Swoole\Coroutine')) {
    /**
     * Swoole coroutine-local storage for concurrent request model
     */
    class SwooleCoroutineStorage implements ServiceStorageInterface
    {
        protected function checkCoroutine(): void
        {
            // getcid() returns -1 when not in a coroutine
            if (\Swoole\Coroutine::getcid() < 0) {
                throw new \RuntimeException('SwooleCoroutineStorage can only be used within a running Coroutine.');
            }
        }

        public function get(): ?ServicesInterface
        {
            $this->checkCoroutine();
            return \Swoole\Coroutine::getContext()['services'] ?? null;
        }

        public function set(ServicesInterface $services): void
        {
            $this->checkCoroutine();
            // getContext() returns a Coroutine\Context object which acts like an array
            \Swoole\Coroutine::getContext()['services'] = $services;
        }

        public function has(): bool
        {
            $this->checkCoroutine();
            return isset(\Swoole\Coroutine::getContext()['services']);
        }

        public function clear(): void
        {
            $this->checkCoroutine();
            if (isset(\Swoole\Coroutine::getContext()['services'])) {
                unset(\Swoole\Coroutine::getContext()['services']);
            }
        }

        public function count(): int
        {
            return -1;
        }
    }
}
