<?php

/**
 * Handle module admin api functions
 *
 * Usage:
 * ```
 * # adminapi.php
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\AdminApiInterface;
 * use Xaraya\Modules\AdminApiTrait;
 *
 * class AdminApi implements AdminApiInterface
 * {
 *     /** @use AdminApiTrait<Module> *\/
 *     use AdminApiTrait;
 *
 *     public function create($args = []) {
 *         // create module item
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

use ixarMod;
use xarMod;
use sys;

sys::import('xaraya.modules.userapitrait');

/**
 * Module class supports admin api methods - available via AdminApiTrait
 */
interface AdminApiInterface extends UserApiInterface
{
    // ...
}

/**
 * Trait to handle admin api functions
 * @template TModule of ModuleInterface|null
 */
trait AdminApiTrait
{
    /** @use UserApiTrait<TModule> */
    use UserApiTrait;

    /**
     * Summary of configure
     * @return void
     */
    public function configure()
    {
        $this->setModType('admin');
        // any state here = default for api load
        xarMod::apiLoad($this->getModName(), $this->getModType(), ixarMod::LOAD_ANYSTATE, $this->getContext());
    }
}
