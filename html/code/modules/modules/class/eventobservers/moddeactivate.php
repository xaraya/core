<?php
/**
 * ModDeactivate Subject Observer
 *
 * This observer is notified after a module is deactivated by the module installer
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
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
        $modName = $subject->getArgs();
        // let any hooks know the module was deactivated    
        xarHooks::notify('ModuleDeactivate', array('objectid' => $modName, 'module' => $modName));
    }
}
