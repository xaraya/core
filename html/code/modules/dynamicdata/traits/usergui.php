<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.7.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\Modules\DynamicData\Traits;

use Xaraya\Modules\UserGuiInterface as CoreGuiInterface;
use Xaraya\Modules\UserGuiTrait as CoreGuiTrait;
use Xaraya\Modules\ModuleInterface;
use sys;

sys::import('xaraya.modules.userguitrait');
sys::import('modules.dynamicdata.traits.otherapi');

/**
 * For documentation purposes only - available via UserGuiTrait
 */
interface UserGuiInterface extends CoreGuiInterface
{
    /**
     * Summary of main
     * @param array<string, mixed> $args
     * @return array<mixed>|string|void
     */
    public function main(array $args = []);

    /**
     * User handle GUI function
     * @param array<string, mixed> $args
     * @return string
     */
    public function handle(array $args = []);
}

/**
 * Trait to handle generic user gui functions for modules with their own DD objects
 *
 * Example:
 * ```
 * use Xaraya\Modules\DynamicData\Traits\UserGuiInterface;
 * use Xaraya\Modules\DynamicData\Traits\UserGuiTrait;
 * use sys;
 *
 * sys::import('modules.dynamicdata.traits.usergui');
 *
 * class MyClassGui implements UserGuiInterface
 * {
 *     use UserGuiTrait;
 * }
 * ```
 * @template TModule of ModuleInterface|null
 */
trait UserGuiTrait
{
    /** @use CoreGuiTrait<TModule> */
    use CoreGuiTrait;
    use OtherApiTrait;

    /**
     * User handle GUI function
     * @param array<string, mixed> $args
     * @return string
     * @see \Xaraya\Routing\ModuleHandler::getRoutes()
     */
    public function handle(array $args = [])
    {
        // use entity and action to avoid conflict with module & func or object & method
        $this->var()->check('entity', $args['entity'], 'str:1');
        if (!empty($args['entity'])) {
            // map entity to object by adding prefix in handle()
            $args['object'] ??= static::$prefix . $args['entity'];
            unset($args['entity']);
        }
        $this->var()->check('action', $args['action'], 'str:1');
        if (!empty($args['action'])) {
            $args['method'] ??= $args['action'];
            unset($args['action']);
        }
        // use the 'default' template instead of the module 'user' template here
        $this->tpl()->setPageTemplateName('default');

        // let the ui handler do the rest
        $interface = $this->data()->getObjectInterface($args);
        return $interface->handle($args, $this->getContext());
    }
}
