<?php

/**
 * Handle single module function as method
 *
 * Usage:
 * ```
 * # class/userapi/get.php
 * namespace Xaraya\Modules\MyFancyModule\UserApi;
 *
 * use Xaraya\Modules\MethodClass;
 * use sys;
 *
 * sys::import('xaraya.modules.method');
 *
 * class GetMethod extends MethodClass
 * {
 *     public function __invoke(array $args = [])
 *     {
 *         // ...
 *         // $context = $this->getContext();
 *         return $data;
 *     }
 * }
 * ```
 *
 * @package core\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules;

use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;
use sys;

sys::import('xaraya.modules.coretrait');
sys::import('xaraya.modules.hookstrait');

/**
 * For documentation purposes only - available via MethodClass
 */
interface MethodInterface extends ContextInterface, CoreInterface, HooksInterface
{
    /**
     * Summary of __invoke
     * @param array<mixed> $args
     * @return mixed
     */
    public function __invoke(array $args = []);
}

/**
 * Handle single module function as method
 * @see https://phpstan.org/blog/generics-by-examples
 * @template TComponent of MethodsInterface|null
 */
class MethodClass implements MethodInterface
{
    use ContextTrait;
    use CoreTrait;
    use HooksTrait;

    protected string $moduleName;          // set in constructor by MethodsTrait::__call()
    protected int $itemtype = 0;
    /** @var TComponent */
    protected ?MethodsInterface $parent;

    /**
     * Summary of __invoke
     * @param array<mixed> $args
     * @return mixed
     */
    public function __invoke(array $args = [])
    {
        return $args;
    }

    /**
     * Summary of __construct
     * @param string $moduleName
     * @param int $itemtype
     * @param TComponent $parent
     */
    public function __construct(string $moduleName, int $itemtype = 0, ?MethodsInterface $parent = null)
    {
        $this->moduleName = $moduleName;
        // pass along itemtype from module class - @todo is this useful/relevant?
        $this->itemtype = $itemtype;
        $this->parent = $parent;
        $this->configure();
    }

    public function configure(): void
    {
        // ...
    }

    /**
     * @return TComponent
     */
    public function getParent(): MethodsInterface|null
    {
        return $this->parent;
    }
}
