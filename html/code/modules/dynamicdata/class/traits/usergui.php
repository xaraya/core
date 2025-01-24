<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject\Traits;

use Xaraya\Modules\UserGuiInterface as CoreGuiInterface;
use Xaraya\Modules\UserGuiTrait as CoreGuiTrait;
use Xaraya\Modules\ModuleInterface;
use sys;

sys::import('xaraya.modules.userguitrait');
sys::import('modules.dynamicdata.class.traits.otherapi');

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
}

/**
 * Trait to handle generic user gui functions for modules with their own DD objects
 *
 * Example:
 * ```
 * use Xaraya\DataObject\Traits\UserGuiInterface;
 * use Xaraya\DataObject\Traits\UserGuiTrait;
 * use sys;
 *
 * sys::import('modules.dynamicdata.class.traits.usergui');
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
     * Summary of main
     * @param array<string, mixed> $args
     * @return array<mixed>|string|void
     */
    public function main(array $args = [])
    {
        // Add standard template variables (module, itemtype and context)
        return $this->mod()->prepare($args);
    }
}
