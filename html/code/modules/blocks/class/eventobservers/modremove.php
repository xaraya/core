<?php

/**
 *
 * @package modules\blocks
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/13.html
 */

sys::import('xaraya.structures.events.observer');
sys::import('xaraya.services.xar');
use Xaraya\Services\xar;

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
        xar::var()->setCached('Blocks.event', 'modremove', $modName);
        //
        // Delete block details for this module.
        //
        // Get block types.
        $blocktypes = xar::mod()->apiFunc(
            'blocks',
            'types',
            'getitems',
            ['module' => $modName]
        );

        // Delete block types.
        if (is_array($blocktypes) && !empty($blocktypes)) {
            foreach ($blocktypes as $blocktype) {
                xar::mod()->apiFunc('blocks', 'types', 'deleteitem', $blocktype);
            }
        }
        xar::var()->delCached('Blocks.event', 'modremove');

    }
}
