<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories;

use Xaraya\Modules\ModuleClass;

/**
 * Get categories module classes via xarMod::getModule()
 */
class Module extends ModuleClass
{
    public function setClassTypes(): void
    {
        parent::setClassTypes();
        // add other class types for categories
        //$this->classtypes['utilapi'] = 'UtilApi';
    }
}
