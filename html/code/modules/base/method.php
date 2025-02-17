<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Base;

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
     * Get module javascript API class for this module
     */
    public function javascriptapi(): UserApiInterface|null
    {
        return $this->getParent()->javascriptapi();
    }

    /**
     * Get module ws API class for this module
     */
    public function wsapi(): UserApiInterface|null
    {
        return $this->getParent()->wsapi();
    }
}
