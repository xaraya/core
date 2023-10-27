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

require __DIR__ . '/dbal_config.php';

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
$filepath = sys::varpath() . '/sqlite/xaraya.sqlite';
$cleardb = true;
$sqliteConn = get_sqlite_conn($filepath, $cleardb);
$withData = true;
copy_database($xarConn, $sqliteConn, $withData);
