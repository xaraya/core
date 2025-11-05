<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules;

use Xaraya\Modules\ModuleClass;

/**
 * Get modules module classes via xar::mod()->getModule()
 */
class Module extends ModuleClass
{
    public function setClassTypes(): void
    {
        parent::setClassTypes();
        // add other class types for modules
        //$this->classtypes['utilapi'] = 'UtilApi';
    }
}
