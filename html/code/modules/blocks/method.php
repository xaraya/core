<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Blocks;

use Xaraya\Modules\MethodClass as CoreMethodClass;
use Xaraya\Modules\ModuleServicesInterface;
use Xaraya\Modules\UserApiInterface;

/**
 * Handle single module function as method from api/gui module class
 * @template TComponent of ModuleServicesInterface
 * @extends CoreMethodClass<TComponent>
 */
class MethodClass extends CoreMethodClass
{
    /**
     * Get module blocks API class for this module
     */
    public function blocksapi(): ?UserApiInterface
    {
        return $this->getParent()->blocksapi();
    }

    /**
     * Get module instances API class for this module
     */
    public function instancesapi(): ?UserApiInterface
    {
        return $this->getParent()->instancesapi();
    }

    /**
     * Get module types API class for this module
     */
    public function typesapi(): ?UserApiInterface
    {
        return $this->getParent()->typesapi();
    }
}
