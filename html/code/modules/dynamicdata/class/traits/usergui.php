<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject\Traits;

use Xaraya\Core\Traits\ContextInterface;
use Xaraya\Core\Traits\ContextTrait;

/**
 * For documentation purposes only - available via UserGuiTrait
 */
interface UserGuiInterface extends ContextInterface
{
    /**
     * Summary of init
     * @param array<string, mixed> $args
     * @return void
     */
    public function init(array $args = []);

    /**
     * Summary of main
     * @param array<string, mixed> $args
     * @return array<mixed>
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
 */
trait UserGuiTrait
{
    use ContextTrait;

    /**
     * Summary of init
     * @param array<string, mixed> $args
     * @return void
     */
    public function init(array $args = []) {}

    /**
     * Summary of main
     * @param array<string, mixed> $args
     * @return array<mixed>
     */
    public function main(array $args = [])
    {
        return $args;
    }
}
