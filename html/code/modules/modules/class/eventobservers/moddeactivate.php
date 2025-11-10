<?php

/**
 * ModDeactivate Subject Observer
 *
 * This observer is notified after a module is deactivated by the module installer
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
**/
sys::import('xaraya.structures.events.observer');

class ModulesModDeactivateObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'modules';
    public function notify(ixarEventSubject $subject)
    {
        $xar = $subject->getServicesClass();
        $modName = $subject->getArgs();
        if ($xar->cache()->withOutput()) {
            $xar->cache()->flushPages('modules');
            // a status update might mean a new menulink and new base homepage
            $xar->cache()->flushPages('base');
        }
        $context = $subject->getContext();
        // let any hooks know the module was deactivated
        $xar->hooked()->notify('ModuleDeactivate', ['objectid' => $modName, 'module' => $modName], $context);
    }
}
