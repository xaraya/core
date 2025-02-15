<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\Modules\DynamicData;

use Xaraya\Modules\MethodClass as CoreMethodClass;
use Xaraya\Modules\ModuleServicesInterface;
use Xaraya\Modules\DynamicData\Traits\UserApiInterface;

/**
 * Handle (traditional) DD api/gui functions via module class
 * Note: this does not replace the object-centric UI handlers or direct use of object methods
 * @template TComponent of ModuleServicesInterface
 * @extends CoreMethodClass<TComponent>
 */
class MethodClass extends CoreMethodClass
{
    /**
     * Get module util API class for this module
     */
    public function utilapi(): UserApiInterface|null
    {
        return $this->getParent()->utilapi();
    }

    /**
     * Get module data API class for this module
     */
    public function dataapi(): UserApiInterface|null
    {
        return $this->getParent()->dataapi();
    }
}
