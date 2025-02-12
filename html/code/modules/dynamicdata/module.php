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

namespace Xaraya\DataObject;

use Xaraya\Modules\ModuleClass;
use Xaraya\Modules\AdminGuiInterface;
use sys;

sys::import('modules.dynamicdata.class.objects');

/**
 * Get dynamicdata module classes via xarMod::getModule()
 */
class Module extends ModuleClass
{
    public function setClassTypes(): void
    {
        parent::setClassTypes();
        // add other class types for this module
        $this->classtypes['testgui'] = 'TestGui';
        $this->classtypes['utilapi'] = 'UtilApi';
        $this->classtypes['dataapi'] = 'DataApi';
        $this->classtypes['restapi'] = 'RestApi';
    }

    public function getTestGUI(): AdminGuiInterface
    {
        $component = $this->getComponent('TestGui');
        assert($component instanceof AdminGuiInterface);
        return $component;
    }
}
