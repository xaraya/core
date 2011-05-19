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

function sql_220_16()
{
    // Define parameters
    $table = xarDB::getPrefix() . '_dynamic_objects';
    
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Move the access data from the config to the access field
    ");
    $data['reply'] = xarML("
        Success!
    ");
    
    // Run the query
    $dbconn  = xarDB::getConn();
    try {
        $dbconn->begin();
        $objects = array(
                'dynamic_objects',
                'dynamic_properties',
                'configurations',
                'dynamicdata_tablefields',
                'module_settings',
                'modules',
                'roles_users',
                'roles_groups',
                'roles_user_settings',
                'themes_user_settings',
                'privileges_baseprivileges',
                'privileges_privileges',
                'roles_user_settings',
                );
        foreach ($objects as $object) {
            $query = "UPDATE $table SET access = config WHERE `name` = '" . $object . "'";              
            $dbconn->Execute($data['sql']);        
            $query = "UPDATE $table SET config = 'a:0:{}' WHERE `name` = '" . $object . "'";              
            $dbconn->Execute($data['sql']);        
        }

        $dbconn->commit();
        
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