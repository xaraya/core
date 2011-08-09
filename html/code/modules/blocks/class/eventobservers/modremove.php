<?php
/**
 * ModRemove Subject Observer
 *
 * This observer is notified before a module is removed by the module installer
**/
sys::import('xaraya.structures.events.observer');
class BlocksModRemoveObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'blocks';
    public function notify(ixarEventSubject $subject)
    {
        $modName = $subject->getArgs();
        //
        // Delete block details for this module.
        //
        // Get block types.
        $blocktypes = xarMod::apiFunc('blocks', 'types', 'getitems',
                                    array('module' => $modName));

        // Delete block types.
        if (is_array($blocktypes) && !empty($blocktypes)) {
            foreach($blocktypes as $blocktype) {
                xarMod::apiFunc('blocks', 'types', 'deleteitem', $blocktype);
            }
        }

    }
}
?>