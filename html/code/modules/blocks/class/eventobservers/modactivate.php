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

/**
 * ModActivate Subject Observer
 *
 * This observer is notified after a module is activated by the module installer
**/
class BlocksModActivateObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'blocks';
    public function notify(ixarEventSubject $subject)
    {
        $modName = $subject->getArgs();
        if (xarCache::isOutputCacheEnabled() && function_exists('xarMod::getName') && xarMod::getName() != 'installer') {
            if (xarOutputCache::isBlockCacheEnabled()) {
                // a status update might mean a new menulink and new base homepage
                xarBlockCache::flushCached('base');
            }
        }
        // refresh block types
        xarMod::apiFunc('blocks', 'types', 'refresh', 
            array('module' => $modName, 'refresh' => true));
    }
}
