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

function sql_220_05()
{    
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Initialising hook system and registering subjects
    ");
    $data['reply'] = xarML("
        Success!
    ");    
    
    try {
        sys::import('xaraya.hooks');
        /* Hook Events */
        // Register modules module hook subjects 
        xarHooks::registerSubject('ModuleModifyconfig', 'module', 'modules');
        xarHooks::registerSubject('ModuleUpdateconfig', 'module', 'modules');
        xarHooks::registerSubject('ModuleRemove', 'module', 'modules');
        xarHooks::registerSubject('ModuleInit', 'module', 'modules');
        xarHooks::registerSubject('ModuleActivate', 'module', 'modules');
        xarHooks::registerSubject('ModuleUpgrade', 'module', 'modules');
        // Module itemtype hook subjects
        xarHooks::registerSubject('ItemtypeCreate', 'itemtype', 'modules');
        xarHooks::registerSubject('ItemtypeDelete', 'itemtype', 'modules');
        xarHooks::registerSubject('ItemtypeView', 'itemtype', 'modules');
        // Module item hook subjects (@TODO: these should no longer apply to roles) 
        xarHooks::registerSubject('ItemNew', 'item', 'modules');
        xarHooks::registerSubject('ItemCreate', 'item', 'modules');
        xarHooks::registerSubject('ItemModify', 'item', 'modules'); 
        xarHooks::registerSubject('ItemUpdate', 'item', 'modules');
        xarHooks::registerSubject('ItemDisplay', 'item', 'modules');
        xarHooks::registerSubject('ItemDelete', 'item', 'modules');
        xarHooks::registerSubject('ItemSubmit', 'item', 'modules');        
        // Transform hooks
        // @TODO: these really need to go away...
        xarHooks::registerSubject('ItemTransform', 'item', 'modules');
        xarHooks::registerSubject('ItemTransforminput', 'item', 'modules');           
        // @TODO: these need evaluating
        xarHooks::registerSubject('ItemFormheader', 'item', 'modules');
        xarHooks::registerSubject('ItemFormaction', 'item', 'modules');
        xarHooks::registerSubject('ItemFormdisplay', 'item', 'modules');
        xarHooks::registerSubject('ItemFormarea', 'item', 'modules');
        // Register base module hook subjects 
        xarHooks::registerSubject('ItemWaitingcontent', 'item', 'base'); 
        // NOTE: ItemSearch is registered by search module 
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