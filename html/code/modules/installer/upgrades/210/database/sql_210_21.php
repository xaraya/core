<?php
/**
 * Upgrade SQL file
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage installer module
 * @link http://xaraya.com/index.php/release/200.html
 */
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */xxxx

function sql_210_21()
{
    // Define parameters
    $table['block_types'] = xarDB::getPrefix() . '_block_types';
    $table['block_instances'] = xarDB::getPrefix() . '_block_instances';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Adding the content type to the content field of blocks of type 'html', 'php', 'finclude' and 'text'
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        
        $types = array('text','html','php','finclude');
        foreach ($types as $type) {
            $data['sql'] = "
            SELECT i.id, i.content FROM $table[block_instances] i, $table[block_types] t WHERE i.type_id = t.id AND t.name = '" . $type . "';
            ";
            $result = $dbconn->Execute($data['sql']);
            while(!$result->EOF){
                list($id, $content) = $result->fields;
                $temp = unserialize($content);
                $temp['content_type'] = $type;
                $content = serialize($temp);
                $data['sql'] = "
                UPDATE $table[block_instances] SET content = '$content' WHERE id = $id;
                ";
                $dbconn->Execute($data['sql']);
                $result->MoveNext();
            }

        }
        $dbconn->commit();
    } catch (Exception $e) {
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