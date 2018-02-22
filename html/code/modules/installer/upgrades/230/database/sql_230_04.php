<?php
/**
 * @package modules\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/200.html
 */

function sql_230_04()
{
    // Define parameters
    $module = 'themes';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Creating themes configurations object
    ");
    $data['reply'] = xarML("
        Success!
    ");    
    
    // inport the object
    $dbconn  = xarDB::getConn();
    try {
        $objects = array(
                       'themes_configurations',
                         );
    
        if(!xarMod::apiFunc('modules','admin','standardinstall',array('module' => $module, 'objects' => $objects))) return;
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