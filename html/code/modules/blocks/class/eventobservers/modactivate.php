<?php

/**
 *
 * @package modules\blocks
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/13.html
 */


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
        $xar = $subject->getServicesClass();
        $modName = $subject->getArgs();
        if ($xar->req()->getModule() != 'installer') {
            // a status update might mean a new menulink and new base homepage
            $xar->cache()->flushBlocks('base');
        }
        // refresh block types
        $xar->mod()->apiFunc(
            'blocks',
            'types',
            'refresh',
            ['module' => $modName, 'refresh' => true]
        );
    }
}
