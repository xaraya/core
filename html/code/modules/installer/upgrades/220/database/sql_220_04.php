<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_220_04()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Initialising event system and registering subjects and observers
    ");
    $data['reply'] = xarML("
        Success!
    ");    

    try {
        // initialise event system
        $systemArgs = array();
        xarEvents::init($systemArgs);        
        // Register base module event subjects
        xarEvents::registerSubject('Event', 'event', 'base');
        xarEvents::registerSubject('ServerRequest', 'server', 'base');
        xarEvents::registerSubject('SessionCreate', 'session', 'base');
        // Register base module event observers
        xarEvents::registerObserver('Event', 'base');
        // Register modules module event subjects
        xarEvents::registerSubject('ModLoad', 'module', 'modules');
        xarEvents::registerSubject('ModApiLoad', 'module', 'modules');
        // Register authsystem event subjects
        xarEvents::registerSubject('UserLogin', 'user', 'authsystem');
        xarEvents::registerSubject('UserLogout', 'user', 'authsystem');
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