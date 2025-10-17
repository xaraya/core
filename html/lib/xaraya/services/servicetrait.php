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

sys::import('xaraya.context.contexttrait');
sys::import('xaraya.context.context');

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
    public function getParent(): mixed;
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
     * Get parent of service class
     */
    public function getParent(): mixed
    {
        // @todo this should only be called when parent has services interface
        assert($this->parent instanceof ServicesInterface);
        return $this->parent;
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
            $this->getParent()->setContext($context);
        }
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
            //$this->context = xarServer::getInstance()?->getContext();
            $this->context = new Context(['source' => $this->parent]);
        }
        return $this->context;
    }
}
