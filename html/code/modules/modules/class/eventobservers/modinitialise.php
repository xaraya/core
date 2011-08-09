<?php
/**
 * ModInitialise Subject Observer
 *
 * This observer is notified after a module is initialised by the module installer
**/
sys::import('xaraya.structures.events.observer');
class ModulesModInitialiseObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'modules';
    public function notify(ixarEventSubject $subject)
    {
        $modName = $subject->getArgs();
        // our only job is to let any hooks know the module was initialised    
        xarHooks::notify('ModuleInit', array('objectid' => $modName, 'module' => $modName));
    }
}
?>