<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks;

use Xaraya\Modules\ModuleClass;

/**
 * Get blocks module classes via xarMod::getModule()
 */
class Module extends ModuleClass
{
    public function setClassTypes(): void
    {
        parent::setClassTypes();
        // add other class types for blocks
        $this->classtypes['blocksapi'] = 'BlocksApi';
        $this->classtypes['instancesapi'] = 'InstancesApi';
        $this->classtypes['typesapi'] = 'TypesApi';
    }
}
