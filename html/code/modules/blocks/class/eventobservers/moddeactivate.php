<?php
/**
 *
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/13.html
 */

sys::import('xaraya.structures.events.observer');

/**
 * ModDeactivate Subject Observer
 *
 * This observer is notified after a module is deactivated by the module installer
**/
class BlocksModDeactivateObserver extends EventObserver implements ixarEventObserver
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