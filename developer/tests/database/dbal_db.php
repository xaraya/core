<?php
/**
 * Entrypoint for experimenting with Doctrine DBAL
 *
 * Note: this assumes you install doctrine/dbal with composer
 * and use composer autoload in the entrypoint, see e.g. db.php
 *
 * $ composer require --dev doctrine/dbal
 * $ head html/db.php
 * <?php
 * ...
 * require dirname(__DIR__).'/vendor/autoload.php';
 * ...
 *
 * https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/index.html
 */
require dirname(__DIR__, 3).'/vendor/autoload.php';

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

function get_xaraya_conn($config = null)
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
    $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
    return $conn;
}

function get_sqlite_conn($clear = true)
{
    $options = [
        'driver' => 'pdo_sqlite',
        'path' => sys::varpath() . '/sqlite/xaraya.sqlite',
    ];
    if ($clear && file_exists($options['path'])) {
        unlink($options['path']);
    }
    $conn = \Doctrine\DBAL\DriverManager::getConnection($options);
    return $conn;
}

function check_schema($conn)
{
    $sm = $conn->createSchemaManager();
    $schema = $sm->introspectSchema();
    //print_r($schema);
    $platform = $conn->getDatabasePlatform();
    $queries = $schema->toSql($platform);
    //print_r($queries);
    return $schema;
}

function check_querybuilder($conn)
{
    $queryBuilder = $conn->createQueryBuilder();
    $queryBuilder
        ->select('id', 'name')
        ->from('users');
    //print_r((string) $queryBuilder);
    print_r($queryBuilder->getSQL());
}

function check_createtable($conn, $name = 'xar_dynamic_data')
{
    $sm = $conn->createSchemaManager();
    $platform = $conn->getDatabasePlatform();
    $table = $sm->introspectTable($name);
    //$sql = $platform->getCreateTableSQL($table, $platform::CREATE_INDEXES | $platform::CREATE_FOREIGNKEYS);
    $sql = $platform->getCreateTableSQL($table);
    print_r($sql);
}

function check_migratetosql($xarConn, $sqliteConn)
{
    $xarSchema = check_schema($xarConn);
    $sqliteSchema = check_schema($sqliteConn);
    $sql = $xarSchema->getMigrateToSql($sqliteSchema, $sqliteConn->getDatabasePlatform());
    print_r($sql);
}

function copy_database($xarConn, $sqliteConn, $withData = false)
{
    $xarPlatform = $xarConn->getDatabasePlatform();
    $sqlitePlatform = $sqliteConn->getDatabasePlatform();

    // https://www.doctrine-project.org/projects/doctrine-dbal/en/3.7/reference/schema-manager.html
    $sm = $xarConn->createSchemaManager();
    //$databases = $sm->listDatabases();
    //print_r($databases);
    $tables = $sm->listTables();
    //print_r($tables);
    foreach ($tables as $table) {
        echo $table->getName() . " columns:\n";
        foreach ($table->getColumns() as $column) {
            $output = ' - ' . $column->getName();
            $type = $column->getType();
            $typeName = \Doctrine\DBAL\Types\Type::lookupName($type);
            $output .= " (" . $typeName . ")\n";
            //$output .= json_encode($column->toArray(), JSON_PRETTY_PRINT) . "\n";
            echo $output;
        }
        $sql = $xarPlatform->getCreateTableSQL($table);
        print_r($sql);
        // remove all charset and collation options from table
        $options = $table->getOptions();
        //print_r($options);
        $table->addOption('charset', null);
        $table->addOption('collation', null);
        // see Doctrine\DBAL\Platforms\SQLite\Comparator method normalizeColumns()
        foreach ($table->getColumns() as $column) {
            // remove all charset and collation options from column
            $options = $column->getPlatformOptions();
            if (empty($options)) {
                continue;
            }
            //print_r($options);
            unset($options['charset']);
            unset($options['collation']);
            $column->setPlatformOptions($options);
        }
        $sql = $sqlitePlatform->getCreateTableSQL($table);
        print_r($sql);
        foreach ($sql as $query) {
            $sqliteConn->executeStatement($query);
        }
        if (!$withData) {
            continue;
        }
        // not very elegant way of doing this, but we don't really care
        $name = $table->getName();
        $data = $xarConn->fetchAllAssociative('SELECT * FROM ' . $name);
        $sqliteConn->beginTransaction();
        try {
            foreach ($data as $row) {
                $sqliteConn->insert($name, $row);
            }
            $sqliteConn->commit();
        } catch (\Exception $e) {
            $sqliteConn->rollBack();
            throw $e;
        }
        echo "Inserted " . count($data) . " items\n";
    }
}

$xarConn = get_xaraya_conn();
$sqliteConn = get_sqlite_conn();
$withData = true;
copy_database($xarConn, $sqliteConn, $withData);
