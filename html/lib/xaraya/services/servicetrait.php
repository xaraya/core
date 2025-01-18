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
 * For documentation purposes only - available via ServiceTrait
 */
interface ServiceInterface extends ContextInterface
{
    public function __construct(object $parent);
    public function getParent(): ServicesInterface;
}

/**
 * Service available via methods
 * @template TParent of ServicesInterface
 */
trait ServiceTrait
{
    use ContextTrait;

    /** @var ?static<TParent> */
    protected static $instance = null;
    /** @var TParent */
    public object $parent;

    /**
     * Create service class for parent
     * @param TParent $parent
     */
    public function __construct(object $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Get parent of service class
     * @return TParent
     */
    public function getParent(): ServicesInterface
    {
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

    /**
     * Summary of getInstance
     * @param TParent $parent
     * @return static<TParent>
     */
    public static function getInstance($parent)
    {
        static::$instance ??= new static($parent);
        return static::$instance;
    }
}

/**
 * Access xar*::* service methods
 *
 * @template TParent of ServicesInterface
 */
class ServiceClass implements ServiceInterface
{
    /** @use ServiceTrait<TParent> */
    use ServiceTrait;
}
