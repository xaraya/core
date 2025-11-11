<?php

/**
 * ModInitialise Subject Observer
 *
 * This observer is notified after a module is initialised by the module installer
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
**/

class ModulesModInitialiseObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'modules';
    public function notify(ixarEventSubject $subject)
    {
        $xar = $subject->getServicesClass();
        $modName = $subject->getArgs();
        $context = $subject->getContext();
        // our only job is to let any hooks know the module was initialised
        $xar->hooked()->notify('ModuleInit', ['objectid' => $modName, 'module' => $modName], $context);
    }
}
