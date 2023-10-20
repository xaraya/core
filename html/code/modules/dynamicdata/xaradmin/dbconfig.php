<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Database configurations used by modules and objects
 */
function dynamicdata_admin_dbconfig(array $args = [])
{
    // Security
    if(!xarSecurity::check('AdminDynamicData')) {
        return;
    }

    extract($args);

    if (!xarVar::fetch('db', 'notempty', $db, '', xarVar::NOT_REQUIRED)) {
        return;
    }

    $data = [];

    if (!empty($db)) {
        if ($db === 'default') {
            $data['db'] = $db;
            return $data;
        }
        [$module, $dbname] = explode('.', $db . '.');
        $databases = xarModVars::get($module, 'databases');
        if (empty($databases)) {
            $databases = [];
        } else {
            $databases = unserialize($databases);
        }
        if ($dbname !== '*' && empty($databases[$dbname])) {
            return $data;
        }
        // @todo save config if needed
        $data['db'] = $db;
        $data['module'] = $module;
        $data['dbname'] = $dbname;
        if ($dbname === '*') {
            $data['database'] = [
                'name' => 'memory',
                'description' => 'In-Memory Database',
                // ...
            ];
        } else {
            $data['database'] = $databases[$dbname];
        }
        $data['database']['databaseType'] ??= 'sqlite3';
        $data['database']['databaseName'] ??= ':memory:';
        $data['database']['databaseHost'] ??= '';
        $data['database']['databasePort'] ??= '';
        $data['database']['userName'] ??= '';
        $data['database']['password'] = '';  // force empty
        $data['database']['databaseCharset'] ??= '';
        $data['database']['prefix'] ??= '';
        $data['database']['external'] ??= '';
        return $data;
    }

    $data['dbconfigs'] = [];

    // find any modules with module variable 'databases'
    $all_modules = xarMod::apiFunc('modules', 'admin', 'getitems');
    foreach ($all_modules as $item) {
        $databases = xarModVars::get($item['name'], 'databases');
        if (empty($databases)) {
            continue;
        }
        $databases = unserialize($databases);
        $data['dbconfigs'][$item['name']] ??= ['objects' => [], 'databases' => []];
        $data['dbconfigs'][$item['name']]['databases'] = $databases;
    }
    // find any objects with config containing dbConnIndex and/or dbConnArgs
    $objectlist = DataObjectMaster::getObjectList(['name' => 'objects', 'fieldlist' => ['name', 'label', 'module_id', 'config']]);
    $all_objects = $objectlist->getItems();
    foreach ($all_objects as $item) {
        if (empty($item['config'])) {
            continue;
        }
        $configuration = unserialize($item['config']);
        if (empty($configuration['dbConnIndex']) && empty($configuration['dbConnArgs'])) {
            continue;
        }
        $data['dbconfigs'][$item['module_id']] ??= ['objects' => [], 'databases' => []];
        if (!empty($configuration['dbConnArgs']) && is_string($configuration['dbConnArgs'])) {
            $configuration['dbConnArgs'] = json_decode($configuration['dbConnArgs'], true);
            if (is_callable($configuration['dbConnArgs'])) {
                $configuration['dbConnArgs'] = 'via callback method';
            }
        }
        $data['dbconfigs'][$item['module_id']]['objects'][$item['name']] = $configuration;
    }
    $data['dbconfigs']['dynamicdata'] ??= ['objects' => [], 'databases' => []];

    return $data;
}
