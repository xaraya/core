<?php
/**
 * ModActivate Subject Observer
 *
 * This observer is notified after a module is activated by the module installer
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
**/
sys::import('xaraya.structures.events.observer');
class ModulesModActivateObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'modules';
    public function notify(ixarEventSubject $subject)
    {
        $modName = $subject->getArgs();
        // refresh prop cache
        // checkme: move this to dd ?
        $modInfo = xarMod::getBaseInfo($modName);
        PropertyRegistration::importPropertyTypes(true, array('modules/' . $modInfo['directory'] . '/xarproperties'));
        // let any hooks know the module was activated    
        xarHooks::notify('ModuleActivate', array('objectid' => $modName, 'module' => $modName));
    }
}
