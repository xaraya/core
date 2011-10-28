<?php
/**
 * ModActivate Subject Observer
 *
 * This observer is notified after a module is activated by the module installer
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
?>