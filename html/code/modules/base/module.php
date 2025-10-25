<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base;

use Xaraya\Modules\ModuleClass;

/**
 * Get base module classes via xar::mod()->getModule()
 */
class Module extends ModuleClass
{
    public function setClassTypes(): void
    {
        parent::setClassTypes();
        // add other class types for base
        $this->classtypes['javascriptapi'] = 'JavascriptApi';
        $this->classtypes['wsapi'] = 'WsApi';
    }
}
