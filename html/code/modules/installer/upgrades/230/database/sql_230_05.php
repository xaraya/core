<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_230_05()
{
    // Define parameters
    $module = 'modules';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Registering Mod* event subjects and observers
    ");
    $data['reply'] = xarML("
        Success!
    ");    
    
    try {
        $systemArgs = array();
        xarEvents::init($systemArgs);
        // register modules module event subjects
        xarEvents::registerSubject('ModInitialise', 'module', 'modules');
        xarEvents::registerSubject('ModActivate', 'module', 'modules');
        xarEvents::registerSubject('ModDeactivate', 'module', 'modules');
        xarEvents::registerSubject('ModRemove', 'module', 'modules');

        // Register modules module event observers
        xarEvents::registerObserver('ModInitialise', 'modules');
        xarEvents::registerObserver('ModActivate', 'modules');
        xarEvents::registerObserver('ModDeactivate', 'modules');
        xarEvents::registerObserver('ModRemove', 'modules');
        
        // Register blocks module event observers 
        xarEvents::registerObserver('ModRemove', 'blocks');
    } catch (Exception $e) {
        // Damn
        $data['success'] = false;
        $data['reply'] = xarML("
        Failed!
        ");
    }
    return $data;

}
?>