<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/200.html
 */

function sql_220_07()
{
    $prefix = xarDB::getPrefix();
    $hooks_table = $prefix . '_hooks';
    $modules_table = $prefix . '_modules';
    
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Registering hooked modules
    ");
    $data['reply'] = xarML("
        Success!
    ");
    
    //Load Table Maintainance API
    sys::import('xaraya.tableddl');    
    $dbconn  = xarDB::getConn();
    try {
        $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
        $dbconn->begin();
        // get the list of available hooks 
        $bindvars = array();
        $query = "SELECT DISTINCT mo.regid, ms.regid, h.s_type
                  FROM $hooks_table h, $modules_table ms, $modules_table mo
                  WHERE h.t_module_id = mo.id
                  AND h.s_module_id = ms.id";
              
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);        
        while($result->next()) {      
            list($observer,$subject,$itemtype) = $result->fields;
            if (empty($itemtype)) $itemtype = 0;
            $inserts[] = "($observer,$subject,$itemtype,0)";
        }    
        $result->close();
        
        /**
         * CREATE TABLE xar_hooks (
         *   observer   integer unsigned NOT NULL,
         *   subject    integer unsigned NOT NULL,
         *   itemtype   integer unsigned NOT NULL
        **/
        $fields = array(
            'observer' => array('type' => 'integer', 'unsigned'=>true, 'null' => false),
            'subject'  => array('type' => 'integer', 'unsigned'=>true, 'null' => false),
            'itemtype' => array('type' => 'integer', 'unsigned'=>true, 'null' => false),
            'scope'    => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
        );

        // Create the hooks table
        $query = xarDBCreateTable($hooks_table, $fields);
        $dbconn->Execute($query);                

        // each entry should be unique
        $index = array(
            'name'   => 'i_'.$prefix.'_hooks',
            'fields' => array('observer', 'subject', 'itemtype', 'scope'),
            'unique' => true
        );        
        $query = xarDBCreateIndex($hooks_table, $index);
        $dbconn->Execute($query);
            
        if (!empty($inserts)) {
            $query = "INSERT INTO $hooks_table (`observer`,`subject`,`itemtype`,`scope`)
                      VALUES " . join(',',$inserts); 
            $dbconn->execute($query);
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