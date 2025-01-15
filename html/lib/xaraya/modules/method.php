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

use sys;

sys::import('xaraya.modules.coretrait');
sys::import('xaraya.modules.hookstrait');

/**
 * For documentation purposes only - available via MethodClass
 */
interface MethodInterface extends CoreInterface, HooksInterface
{
    /**
     * Summary of __invoke
     * @param array<mixed> $args
     * @return mixed
     */
    public function __invoke(array $args = []);
    public function configure(): void;
    public function getParent(): MethodsInterface|null;
}

/**
 * Handle single module function as method from api/gui module class
 *
 * The instance will be created by the api/gui module class
 * and configured with the right module, itemtype and parent
 *
 * Available methods:
 * - __invoke(array $args = []) This contains the actual method code
 * - configure() Provide additional method configuration when created
 * - getParent() Get parent api/gui module class to call other methods
 *   or access other api/gui module classes from this instance
 *
 * Inherited methods:
 * - Module:
 *   - getModName() Get name for this module in module class or method
 *   - getModId() Get module registry ID for this module
 *   - getItemType() Get item type in this module class
 *   - setItemType($itemtype = 0) Set item type in this module class
 *   - getModVar($varName) Get module variable for this module
 *   - setModVar($varName, $value) Set module variable for this module
 * - Security:
 *   - checkAccess($mask, $action = '', $instance = null) Check access based on security mask or module action
 *   - genAuthKey() Generate authorisation key for this module
 *   - confirmAuthKey($name = 'authid') Confirm authorisation key for this module
 * - Variable:
 *   - fetch($name, $validation, &$value, $defaultValue = null, $flags, $prep) Fetch variable by name, with validation, default, flags and prep
 * - Controller:
 *   - getUrl($modType = 'user', $funcName = 'main', $args = []) Get url for this module type function
 *   - redirect($url, $httpResponse = null) Send redirect to url and exit
 * - Multi-language:
 *   - translate($rawstring, ...$args) Translate string with optional arguments
 * - System:
 *   - exit($status = 0) Call exit() - override for non-blocking servers, php unit tests or elsewhere
 *
 * @template TComponent of MethodsInterface|null
 */
class MethodClass implements MethodInterface
{
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
     * @param string $modName
     * @param int $itemtype
     * @param TComponent $parent
     */
    public function __construct(string $modName, int $itemtype = 0, ?MethodsInterface $parent = null)
    {
        $this->setModName($modName);
        // pass along itemtype from module class - @todo is this useful/relevant?
        $this->setItemType($itemtype);
        $this->setParent($parent);
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

    /**
     * @param TComponent $parent
     */
    public function setParent(?MethodsInterface $parent): void
    {
        $this->parent = $parent;
    }
}
