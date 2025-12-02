<?php

/**
 * Handle module admin gui functions
 *
 * Usage:
 * ```
 * # admingui.php
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\AdminGuiInterface;
 * use Xaraya\Modules\AdminGuiTrait;
 *
 * class AdminGui implements AdminGuiInterface
 * {
 *     /** @use AdminGuiTrait<Module> *\/
 *     use AdminGuiTrait;
 *
 *     public function main($args = []) {
 *         // get main admin overview
 *         // $context = $this->getContext();
 *         return $output;
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

use ixarMod;

/**
 * Module class supports admin gui methods - available via AdminGuiTrait
 */
interface AdminGuiInterface extends UserGuiInterface
{
    // ...
}

/**
 * Trait to handle admin gui functions
 * @template TModule of ModuleInterface|null
 */
trait AdminGuiTrait
{
    /** @use UserGuiTrait<TModule> */
    use UserGuiTrait;

    /**
     * Summary of configure
     * @return void
     */
    public function configure()
    {
        $this->setModType('admin');
        // any state here = otherwise during module init(), any GUI hook functions registered will throw ModuleNotActiveException
        $this->mod()->load($this->getModName(), $this->getModType(), ixarMod::LOAD_ANYSTATE);
    }
}
