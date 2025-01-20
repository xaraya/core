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
    public function __construct(ServicesInterface $parent);
    public function getParent(): ServicesInterface;
}

/**
 * Service available via methods
 */
trait ServiceTrait
{
    use ContextTrait;

    public ServicesInterface $parent;

    /**
     * Create service class for parent
     */
    public function __construct(ServicesInterface $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Get parent of service class
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
     * Summary of create
     * @todo could be called from ServiceFactory - currently not used
     */
    public static function create(ServicesInterface $parent): static
    {
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
