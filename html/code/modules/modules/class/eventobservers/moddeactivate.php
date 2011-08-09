<?php
/**
 * ModDeactivate Subject Observer
 *
 * This observer is notified after a module is deactivated by the module installer
**/
sys::import('xaraya.structures.events.observer');
class ModulesModDeactivateObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'modules';
    public function notify(ixarEventSubject $subject)
    {
        $modName = $subject->getArgs();
        // let any hooks know the module was deactivated    
        xarHooks::notify('ModuleDeactivate', array('objectid' => $modName, 'module' => $modName));
    }
}
?>