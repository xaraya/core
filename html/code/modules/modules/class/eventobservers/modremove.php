<?php
/**
 * ModRemove Subject Observer
 *
 * This observer is notified before a module is removed by the module installer
**/
sys::import('xaraya.structures.events.observer');
class ModulesModRemoveObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'modules';
    public function notify(ixarEventSubject $subject)
    {
        $modName = $subject->getArgs();
        // Delete any module variables that the module cleanup function might have missed.
        xarModVars::delete_all($modName);
        // Delete any masks still around
        xarRemoveMasks($modName);
        // check and reset the defaultmodule if we're about to remove it
        if ($modName == xarModVars::get('modules', 'defaultmodule')) 
            xarModVars::set('modules', 'defaultmodule','base');
        // let any hooks know the module is being removed    
        xarHooks::notify('ModuleRemove', array('objectid' => $modName, 'module' => $modName));

    }
}
?>