<?php

/**
 * ModActivate Subject Observer
 *
 * This observer is notified after a module is activated by the module installer
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
**/
sys::import('xaraya.structures.events.observer');

class ModulesModActivateObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'modules';
    public function notify(ixarEventSubject $subject)
    {
        $xar = $subject->getServicesClass();
        $modName = $subject->getArgs();
        // refresh prop cache
        // @todo move this to dd ?
        $modInfo = $xar->mod()->getBaseInfo($modName);
        if (empty($modInfo)) {
            return;
        }
        $xar->prop()->importPropertyTypes(true, ['modules/' . $modInfo['directory'] . '/xarproperties']);
        if ($xar->cache()->withOutput() && function_exists('xarMod::getName') && $xar->mod()->getName() != 'installer') {
            $xar->cache()->flushPages('modules');
            // a status update might mean a new menulink and new base homepage
            $xar->cache()->flushPages('base');
        }
        $context = $subject->getContext();
        // let any hooks know the module was activated
        $xar->hooked()->notify('ModuleActivate', ['objectid' => $modName, 'module' => $modName], $context);
    }
}
