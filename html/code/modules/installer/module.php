<?php

/**
 * @package modules\installer
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Installer;

use Xaraya\Modules\ModuleClass;

/**
 * Get installer module classes via xar::mod()->getModule()
 */
class Module extends ModuleClass
{
    public function setClassTypes(): void
    {
        parent::setClassTypes();
        // add other class types for installer
        //$this->classtypes['utilapi'] = 'UtilApi';
    }
}
