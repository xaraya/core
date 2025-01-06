<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.5.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject\Traits;

use Xaraya\Modules\UserGuiInterface as CoreGuiInterface;
use Xaraya\Modules\UserGuiTrait as CoreGuiTrait;
use sys;

sys::import('xaraya.modules.userguitrait');

/**
 * For documentation purposes only - available via UserGuiTrait
 */
interface UserGuiInterface extends CoreGuiInterface
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
    use CoreGuiTrait;

    /** @var UserApiInterface */
    protected $api;

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
        // Pass along the context for xarTpl::module() if needed
        $args['context'] ??= $this->getContext();
        return $args;
    }
}
