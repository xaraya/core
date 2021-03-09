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
sys::import('modules.dynamicdata.class.rest.builder');
/**
 * Test APIs
 */
function dynamicdata_admin_test_apis(array $args=[])
{
    // Security
    if (!xarSecurity::check('EditDynamicData')) {
        return;
    }

    extract($args);

    xarVar::fetch('restapi', 'array', $restapi, [], xarVar::NOT_REQUIRED);
    xarVar::fetch('graphql', 'array', $graphql, [], xarVar::NOT_REQUIRED);
    xarVar::fetch('object_new', 'isset', $object_new, '', xarVar::NOT_REQUIRED);
    if (!empty($object_new)) {
        xarVar::fetch('restapi_new', 'isset', $restapi_new, '', xarVar::NOT_REQUIRED);
        if (!empty($restapi_new)) {
            $restapi[$object_new] = 'on';
        }
        xarVar::fetch('graphql_new', 'isset', $graphql_new, '', xarVar::NOT_REQUIRED);
        if (!empty($graphql_new)) {
            $graphql[$object_new] = 'on';
        }
    }
    xarVar::fetch('module_new', 'isset', $module_new, '', xarVar::NOT_REQUIRED);
    if (!empty($module_new)) {
        xarVar::fetch('restapi_module', 'isset', $restapi_module, '', xarVar::NOT_REQUIRED);
        if (!empty($restapi_module)) {
            $restapi[$module_new] = 'on';
        }
    }
    xarVar::fetch('tokenstorage', 'isset', $tokenstorage, 'database', xarVar::NOT_REQUIRED);
    xarVar::fetch('querycomplexity', 'isset', $queryComplexity, 0, xarVar::NOT_REQUIRED);
    xarVar::fetch('querydepth', 'isset', $queryDepth, 0, xarVar::NOT_REQUIRED);
    $restapilist = [];
    $graphqllist = [];
    if (!empty($restapi) && !empty($graphql) && xarSec::confirmAuthKey()) {
        $restapilist = array_keys($restapi);
        xarModVars::set('dynamicdata', 'restapi_object_list', serialize($restapilist));
        $graphqllist = array_keys($graphql);
        xarModVars::set('dynamicdata', 'graphql_object_list', serialize($graphqllist));
        xarModVars::set('dynamicdata', 'restapi_token_storage', $tokenstorage);
        xarModVars::set('dynamicdata', 'graphql_query_complexity', intval($queryComplexity));
        xarModVars::set('dynamicdata', 'graphql_query_depth', intval($queryDepth));
    } else {
        $restapiserial = xarModVars::get('dynamicdata', 'restapi_object_list');
        if (!empty($restapiserial)) {
            $restapilist = unserialize($restapiserial);
        }
        $graphqllist = [];
        $graphqlserial = xarModVars::get('dynamicdata', 'graphql_object_list');
        if (!empty($graphqlserial)) {
            $graphqllist = unserialize($graphqlserial);
        }
        $tokenstorage = xarModVars::get('dynamicdata', 'restapi_token_storage');
        $queryComplexity = xarModVars::get('dynamicdata', 'graphql_query_complexity');
        $queryDepth = xarModVars::get('dynamicdata', 'graphql_query_depth');
    }

    DataObjectRESTBuilder::init();
    if (!xarVar::fetch('create_rst', 'notempty', $create_rst, 0, xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!empty($create_rst)) {
        DataObjectRESTBuilder::create_openapi($restapilist, $tokenstorage);
        xarController::redirect(xarServer::getCurrentURL(['create_rst'=> null]));
        return true;
    }
    if (!xarVar::fetch('create_gql', 'notempty', $create_gql, 0, xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!empty($create_gql)) {
        $root = sys::root();
        // flat install supporting symlinks
        if (empty($root)) {
            $vendor = realpath(dirname(realpath($_SERVER['SCRIPT_FILENAME'])) . '/../vendor');
        } else {
            $vendor = realpath($root . 'vendor');
        }
        require_once $vendor .'/autoload.php';
        sys::import('modules.dynamicdata.class.graphql');
        $extraTypes = [];
        if (!empty($graphqllist)) {
            $clazz = xarGraphQL::get_type_class("buildtype");
            foreach ($graphqllist as $name) {
                $type = $clazz::singularize($name);
                if (xarGraphQL::has_type($type)) {
                    continue;
                }
                $extraTypes[] = $type;
            }
        }
        xarGraphQL::dump_schema($extraTypes, $queryComplexity, $queryDepth);
        xarController::redirect(xarServer::getCurrentURL(['create_gql'=> null]));
        return true;
    }

    $data = [];
    $openapi = sys::varpath() . '/cache/api/openapi.json';
    if (file_exists($openapi)) {
        $data['openapi'] = $openapi;
    }
    $schema = sys::varpath() . '/cache/api/schema.graphql';
    if (file_exists($schema)) {
        $data['schema'] = $schema;
    }
    $data['restapilist'] = $restapilist;
    $data['graphqllist'] = $graphqllist;
    $mergedlist = array_unique(array_merge($restapilist, $graphqllist));
    $data['objects'] = DataObjectRESTBuilder::get_potential_objects($mergedlist);
    $known_objects = [];
    foreach ($data['objects'] as $item) {
        array_push($known_objects, $item['name']);
    }
    $objectlist = DataObjectMaster::getObjectList(['name' => 'objects', 'fieldlist' => ['name', 'label']]);
    $all_objects = $objectlist->getItems();
    $data['otherlist'] = [];
    foreach ($all_objects as $item) {
        if (!in_array($item['name'], $known_objects)) {
            array_push($data['otherlist'], $item);
        }
    }
    $data['modules'] = DataObjectRESTBuilder::get_potential_modules($mergedlist);
    $all_modules = xarMod::apiFunc('modules', 'admin', 'getitems');
    $data['othermodules'] = [];
    foreach ($all_modules as $item) {
        if (!array_key_exists($item['name'], $data['modules'])) {
            array_push($data['othermodules'], $item);
        }
    }

    $data['tokenstorage'] = $tokenstorage;
    $data['storagetypes'] = [
        'filesystem' => [
            'name'    => 'filesystem',
            'label'   => 'Filesystem',
            'enabled' => false,
        ],
        'database' => [
            'name'    => 'database',
            'label'   => 'Database',
            'enabled' => true,
        ],
        'apcu' => [
            'name'    => 'apcu',
            'label'   => 'APC User Cache (APCu)',
            'enabled' => function_exists('apcu_fetch') ? true : false,
        ],
    ];
    $data['querycomplexity'] = $queryComplexity;
    $data['querydepth'] = $queryDepth;

    xarTpl::setPageTemplateName('admin');

    return $data;
}
