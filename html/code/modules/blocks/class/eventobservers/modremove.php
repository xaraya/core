<?php
/**
 *
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/13.html
 */

sys::import('xaraya.structures.events.observer');

/**
 * ModRemove Subject Observer
 *
 * This observer is notified before a module is removed by the module installer
**/
class BlocksModRemoveObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'blocks';
    public function notify(ixarEventSubject $subject)
    {
        $modName = $subject->getArgs();
        xarVarSetCached('Blocks.event','modremove', $modName);
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
        xarVarDelCached('Blocks.event','modremove', $modName);

    }
}
?>