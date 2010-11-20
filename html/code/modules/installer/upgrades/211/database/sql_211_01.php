<?php

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