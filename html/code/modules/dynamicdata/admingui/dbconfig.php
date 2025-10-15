<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminGui;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminGui;
use Xaraya\Modules\DynamicData\UtilApi;
use Exception;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata admin dbconfig function
 * @extends MethodClass<AdminGui>
 */
class DbconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Database configurations used by modules and objects
     * @see AdminGui::dbconfig()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        extract($args);

        $this->var()->check('db', $db, 'notempty', '');
        $this->var()->check('obj', $obj, 'notempty', '');
        /** @var UtilApi $utilapi */
        $utilapi = $this->utilapi();

        $data = [];

        if (!empty($db)) {
            if ($db === 'default') {
                $data['db'] = $db;
                return $data;
            }
            [$module, $dbname] = explode('.', $db . '.');
            $databases = $utilapi->getDatabases($module);
            if ($dbname !== '*' && empty($databases[$dbname])) {
                return $data;
            }
            $config = null;
            $this->var()->check('config', $config, 'array', []);
            if (!empty($config) && is_array($config) && $this->sec()->confirmAuthKey('dynamicdata')) {
                $config = array_filter($config);
                if (!empty($config['name'])) {
                    // create/update database config
                    if ($dbname !== '*' && $dbname !== $config['name']) {
                        unset($databases[$dbname]);
                    }
                    $databases[$config['name']] = $config;
                    $utilapi->saveDatabases($databases, $module);
                    $dbname = $config['name'];
                } elseif ($dbname !== '*') {
                    // delete database config
                    unset($databases[$dbname]);
                    $utilapi->saveDatabases($databases, $module);
                    $dbname = '*';
                }
                $this->ctl()->redirect($this->mod()->getURL(
                    'admin',
                    'dbconfig',
                ));
                return true;
            }
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
                $configuration = $utilapi->getObjectConfig($objectname);
            } else {
                $configuration = [
                    'name' => $objectname,
                ];
            }
            $config = null;
            $this->var()->check('config', $config, 'array', []);
            if (!empty($config) && is_array($config) && $this->sec()->confirmAuthKey('dynamicdata')) {
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
            $data['databases'] = $utilapi->getDatabases($module);
            $data['config']['dbconfig'] ??= '';
            return $data;
        }

        $data['dbconfigs'] = [];

        // find any modules with module variable 'databases'
        $all_databases = $utilapi->getAllDatabases();
        foreach ($all_databases as $modname => $databases) {
            $data['dbconfigs'][$modname] ??= ['objects' => [], 'databases' => []];
            $data['dbconfigs'][$modname]['databases'] = $databases;
        }
        // find any objects with config containing dbConnIndex and/or dbConnArgs
        $objectlist = $this->data()->getObjectList(['name' => 'objects', 'fieldlist' => ['name', 'label', 'module_id', 'datastore', 'config']]);
        $all_objects = $objectlist->getItems();
        foreach ($all_objects as $item) {
            if (empty($item['config'])) {
                continue;
            }
            try {
                $configuration = unserialize($item['config']);
            } catch (Exception $e) {
                echo "Error unserializing config '" . $item['config'] . "' for object '" . $item['name'] . "':\n";
                echo $e->getMessage();
                $configuration = [];
            }
            if (empty($configuration['dbConnIndex']) && empty($configuration['dbConnArgs'])) {
                continue;
            }
            $configuration['dbConnIndex'] ??= 1;
            $data['dbconfigs'][$item['module_id']] ??= ['objects' => [], 'databases' => []];
            if (!empty($configuration['dbConnArgs']) && is_string($configuration['dbConnArgs'])) {
                $configuration['dbConnArgs'] = json_decode($configuration['dbConnArgs'], true);
                if (is_callable($configuration['dbConnArgs'])) {
                    $configuration['dbConnArgs'] = 'via callback method';
                } elseif (!empty($configuration['dbConnArgs']['databaseConfig'])) {
                    $configuration['dbConnIndex'] = $configuration['dbConnArgs']['databaseConfig'];
                    $configuration['dbConnArgs'] = '= database config';
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
}
