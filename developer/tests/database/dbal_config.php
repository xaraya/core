<?php
/**
 * Doctrine DBAL Configuration for other test scripts
 */
require_once dirname(__DIR__, 3).'/vendor/autoload.php';

// initialize bootstrap
sys::init();

// see lib/xaraya/database.php
function get_xaraya_config()
{
    // Decode encoded DB parameters
    // These need to be there
    $userName = xarSystemVars::get(sys::CONFIG, 'DB.UserName');
    $password = xarSystemVars::get(sys::CONFIG, 'DB.Password');
    $persistent = null;
    try {
        $persistent = xarSystemVars::get(sys::CONFIG, 'DB.Persistent');
    } catch(VariableNotFoundException $e) {
        $persistent = null;
    }
    try {
        if (xarSystemVars::get(sys::CONFIG, 'DB.Encoded') == '1') {
            $userName = base64_decode($userName);
            $password  = base64_decode($password);
        }
    } catch(VariableNotFoundException $e) {
        // doesnt matter, we assume not encoded
    }

    // Hive off the port if there is one added as part of the host
    $host = xarSystemVars::get(sys::CONFIG, 'DB.Host');
    $host_parts = explode(':', $host);
    $host = $host_parts[0];
    $port = isset($host_parts[1]) ? $host_parts[1] : '';

    // Optionals dealt with, do the rest inline
    $systemArgs = array('userName'        => $userName,
                        'password'        => $password,
                        'databaseHost'    => $host,
                        'databasePort'    => $port,
                        'databaseType'    => xarSystemVars::get(sys::CONFIG, 'DB.Type'),
                        'databaseName'    => xarSystemVars::get(sys::CONFIG, 'DB.Name'),
                        'databaseCharset' => xarSystemVars::get(sys::CONFIG, 'DB.Charset'),
                        'persistent'      => $persistent,
                        'prefix'          => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix'));
    return $systemArgs;
}

function get_xaraya_params($config = null)
{
    if (empty($config)) {
        $config = get_xaraya_config();
    }
    $connectionParams = array(
        'dbname' => $config['databaseName'],
        'user' => $config['userName'],
        'password' => $config['password'],
        'host' => $config['databaseHost'],
        'port' => intval($config['databasePort']),
        'driver' => $config['databaseType'],
        'charset' => $config['databaseCharset'],
    );
    return $connectionParams;
}

function get_xaraya_conn($config = null)
{
    $connectionParams = get_xaraya_params($config);
    $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
    return $conn;
}

function get_sqlite_params($filepath = null)
{
    $filepath ??= dirname(__DIR__, 3).'/html/code/modules/library/xardata/metadata.db';
    return [
        'driver' => 'sqlite3',  // 'pdo_sqlite',
        //'path' => sys::varpath() . '/sqlite/xaraya.sqlite',
        'path' => $filepath,
    ];
}

function get_sqlite_conn($filepath = null, $clear = false)
{
    $options = get_sqlite_params($filepath);
    if ($clear && file_exists($options['path'])) {
        unlink($options['path']);
    }
    $conn = \Doctrine\DBAL\DriverManager::getConnection($options);
    return $conn;
}
