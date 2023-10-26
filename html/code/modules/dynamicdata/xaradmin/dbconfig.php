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
sys::import('modules.dynamicdata.class.utilapi');
use Xaraya\DataObject\UtilApi;

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
    if (!xarVar::fetch('obj', 'notempty', $obj, '', xarVar::NOT_REQUIRED)) {
        return;
    }

    $data = [];

    if (!empty($db)) {
        if ($db === 'default') {
            $data['db'] = $db;
            return $data;
        }
        [$module, $dbname] = explode('.', $db . '.');
        $databases = UtilApi::getDatabases($module);
        if ($dbname !== '*' && empty($databases[$dbname])) {
            return $data;
        }
        $config = null;
        xarVar::fetch('config', 'array', $config, array(), xarVar::DONT_SET);
        if (!empty($config) && is_array($config) && xarSec::confirmAuthKey('dynamicdata')) {
            $config = array_filter($config);
            if (!empty($config['name'])) {
                // create/update database config
                if ($dbname !== '*' && $dbname !== $config['name']) {
                    unset($databases[$dbname]);
                }
                $databases[$config['name']] = $config;
                UtilApi::saveDatabases($databases, $module);
                $dbname = $config['name'];
            } elseif ($dbname !== '*') {
                // delete database config
                unset($databases[$dbname]);
                UtilApi::saveDatabases($databases, $module);
                $dbname = '*';
            }
            xarController::redirect(xarController::URL(
                'dynamicdata',
                'admin',
                'dbconfig',
            ));
            return true;
        }
        // @todo save config if needed
        $data['db'] = $db;
        $data['module'] = $module;
        $data['dbname'] = $dbname;
        if ($dbname === '*') {
            $data['config'] = [
                'name' => 'memory',
                'description' => 'In-Memory Database',
                // ...
            ];
        } else {
            $data['config'] = $databases[$dbname];
        }
        $data['config']['databaseType'] ??= 'sqlite3';
        $data['config']['databaseName'] ??= ':memory:';
        $data['config']['databaseHost'] ??= '';
        $data['config']['databasePort'] ??= '';
        $data['config']['userName'] ??= '';
        $data['config']['password'] = '';  // force empty
        $data['config']['databaseCharset'] ??= '';
        $data['config']['prefix'] ??= '';
        $data['config']['external'] ??= '';
        return $data;
    }
    if (!empty($obj)) {
        [$module, $objectname] = explode('.', $obj . '.');
        $data['obj'] = $obj;
        $data['module'] = $module;
        if ($objectname !== '*') {
            $configuration = UtilApi::getObjectConfig($objectname);
        } else {
            $configuration = [
                'name' => $objectname,
            ];
        }
        $config = null;
        xarVar::fetch('config', 'array', $config, array(), xarVar::DONT_SET);
        if (!empty($config) && is_array($config) && xarSec::confirmAuthKey('dynamicdata')) {
            $config = array_filter($config);
            echo var_export($config, true);
            // @todo update object config
        }
        $data['config'] = $configuration;
        $data['config']['name'] ??= '';
        $data['config']['class'] ??= 'DataObject';
        $data['config']['filepath'] ??= 'auto';
        $data['config']['datastore'] ??= 'relational';
        $data['config']['dbConnIndex'] ??= 0;
        $data['config']['dbConnArgs'] ??= [
            'databaseType' => '',
            'databaseName' => '',
        ];
        $data['config']['callable'] ??= [
            'class' => '',
            'method' => 'getDbConnArgs',
        ];
        $data['config']['objectid'] ??= 0;
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
    $objectlist = DataObjectMaster::getObjectList(['name' => 'objects', 'fieldlist' => ['name', 'label', 'module_id', 'datastore', 'config']]);
    $all_objects = $objectlist->getItems();
    foreach ($all_objects as $item) {
        if (empty($item['config'])) {
            continue;
        }
        $configuration = unserialize($item['config']);
        if (empty($configuration['dbConnIndex']) && empty($configuration['dbConnArgs'])) {
            continue;
        }
        $configuration['dbConnIndex'] ??= 1;
        $data['dbconfigs'][$item['module_id']] ??= ['objects' => [], 'databases' => []];
        if (!empty($configuration['dbConnArgs']) && is_string($configuration['dbConnArgs'])) {
            $configuration['dbConnArgs'] = json_decode($configuration['dbConnArgs'], true);
            if (is_callable($configuration['dbConnArgs'])) {
                $configuration['dbConnArgs'] = 'via callback method';
            } else {
                $configuration['dbConnArgs'] = 'with parameters';
            }
        }
        // show currrent datastore setting here too
        $configuration['datastore'] = $item['datastore'];
        $data['dbconfigs'][$item['module_id']]['objects'][$item['name']] = $configuration;
    }
    $data['dbconfigs']['dynamicdata'] ??= ['objects' => [], 'databases' => []];

    return $data;
}
