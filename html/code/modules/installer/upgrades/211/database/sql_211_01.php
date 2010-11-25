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

function sql_211_01()
{
    // Define parameters
    $table = xarDB::getPrefix() . '_modules';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Upgrading the core module version numbers
    ");
    $data['reply'] = xarML("
        Done!
    ");
    $core_modules = array(
                            'authsystem',
                            'base',
                            'blocks',
                            'dynamicdata',
                            'installer',
                            'mail',
                            'modules',
                            'privileges',
                            'roles',
                            'themes',
    );
    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        foreach ($core_modules as $core_module) {
            $data['sql'] = "
            UPDATE $table SET version = '2.1.1' WHERE `name` = '" . $core_module . "';
            ";
            $dbconn->Execute($data['sql']);
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