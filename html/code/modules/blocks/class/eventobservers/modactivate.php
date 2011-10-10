<?php
/**
 * ModActivate Subject Observer
 *
 * This observer is notified after a module is activated by the module installer
**/
sys::import('xaraya.structures.events.observer');
class BlocksModActivateObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'blocks';
    public function notify(ixarEventSubject $subject)
    {
        $modName = $subject->getArgs();
        // refresh block types
        xarMod::apiFunc('blocks', 'types', 'refresh', 
            array('module' => $modName, 'refresh' => true));
    }
}
?>