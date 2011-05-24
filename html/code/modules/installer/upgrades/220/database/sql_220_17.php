<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_220_17()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Refresh the properties cache
    ");
    $data['reply'] = xarML("
        Success!
    ");
    
    // Run the task
    $dbconn  = xarDB::getConn();
    try {
        sys::import('modules.dynamicdata.class.properties.registration');   
        $proptypes = PropertyRegistration::importPropertyTypes(0,array('modules/base'));
        } catch (Exception $e) { throw($e);
        // Damn
        $dbconn->rollback();
        $data['success'] = false;
        $data['reply'] = xarML("
        Failed!
        ");
    }
    return $data;   
    
}
?>