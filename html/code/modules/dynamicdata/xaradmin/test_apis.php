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
function dynamicdata_admin_test_apis(array $args = [])
{
    // Security
    if (!xarSecurity::check('EditDynamicData')) {
        return;
    }

    extract($args);

    xarVar::fetch('tab', 'isset', $tab, null, xarVar::NOT_REQUIRED);
    if (!empty($tab) && in_array($tab, ['swagger-ui', 'datatables', 'playground'])) {
        $testDir = dirname(__DIR__) . '/xartests/';
        $testFile = $testDir . $tab . '.html';
        if (file_exists($testFile)) {
            $contents = file_get_contents($testFile);
            if (strpos(xarServer::getCurrentURL(), '/dynamicdata/admin/test_apis') !== false) {
                // using index.php/dynamicdata/admin/test_apis or similar
                $contents = str_replace('../../../../', '../../../', $contents);
            } else {
                // using index.php?module=dynamicdata&type=admin&func=test_apis
                $contents = str_replace('../../../../', './', $contents);
            }
            // use 'passthru' page template to output the contents as is here
            xarTpl::setPageTemplateName('passthru');
            return $contents;
        }
    }
    if (!empty($tab) && in_array($tab, ['openapi.json', 'schema.graphql'])) {
        $apiDir = sys::varpath() . '/cache/api/';
        $apiFile = $apiDir . $tab;
        if (file_exists($apiFile)) {
            //$contents = file_get_contents($apiFile);
            // use 'passthru' page template to output the contents as is here
            //xarTpl::setPageTemplateName('passthru');
            //return $contents;
            // see session-less page caching
            //sys::import('xaraya.caching.output.page');
            $cacheCode = md5($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
            //xarPageCache::$cacheCode = $cacheCode;
            $modtime = filemtime($apiFile);
            //xarPageCache::sendHeaders($modtime);
            $etag = $cacheCode.$modtime;
            $match = $_SERVER['HTTP_IF_NONE_MATCH'] ?? null;
            if (!empty($match) && $match == $etag) {
                header('HTTP/1.1 304 Not Modified');
                header("Cache-Control: public, must-revalidate");
                exit;
            }
            //header("Expires: " .
            //       gmdate("D, d M Y H:i:s", $modtime + xarPageCache::$cacheTime) .
            //       " GMT");
            //header("Cache-Control: public, max-age=" . xarPageCache::$cacheTime);
            //header("Expires: 0");
            header("Cache-Control: public, must-revalidate");
            header("ETag: $etag");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s", $modtime) . " GMT");
            header('Content-Type: text/plain; charset=utf-8');
            //header("Pragma: public");
            // send the content of the file to the browser
            @readfile($apiFile);
            // we're done here !
            exit;
        }
    }
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
        xarVar::fetch('graphql_module', 'isset', $graphql_module, '', xarVar::NOT_REQUIRED);
        if (!empty($graphql_module)) {
            $graphql[$module_new] = 'on';
        }
    }
    xarVar::fetch('tokenstorage', 'isset', $storageType, 'database', xarVar::NOT_REQUIRED);
    xarVar::fetch('tokenexpires', 'isset', $tokenExpires, '12:00:00', xarVar::NOT_REQUIRED);
    if (!empty($tokenExpires)) {
        [$hour, $min, $sec] = explode(':', $tokenExpires);
        $tokenExpires = (((intval($hour) * 60) + intval($min)) * 60) + intval($sec);
    } else {
        $tokenExpires = 12 * 60 * 60;  // 12 hours
    }
    xarVar::fetch('querycomplexity', 'isset', $queryComplexity, 0, xarVar::NOT_REQUIRED);
    xarVar::fetch('querydepth', 'isset', $queryDepth, 0, xarVar::NOT_REQUIRED);
    xarVar::fetch('enabletimer', 'isset', $enableTimer, false, xarVar::NOT_REQUIRED);
    xarVar::fetch('tracepath', 'isset', $tracePath, false, xarVar::NOT_REQUIRED);
    xarVar::fetch('enablecache', 'isset', $enableCache, false, xarVar::NOT_REQUIRED);
    xarVar::fetch('cacheplan', 'isset', $cachePlan, false, xarVar::NOT_REQUIRED);
    xarVar::fetch('cachedata', 'isset', $cacheData, false, xarVar::NOT_REQUIRED);
    xarVar::fetch('cacheoperation', 'isset', $cacheOperation, false, xarVar::NOT_REQUIRED);
    $restapilist = [];
    $graphqllist = [];
    if (!empty($restapi) && !empty($graphql) && xarSec::confirmAuthKey()) {
        $restapilist = array_keys($restapi);
        xarModVars::set('dynamicdata', 'restapi_object_list', serialize($restapilist));
        $graphqllist = array_keys($graphql);
        xarModVars::set('dynamicdata', 'graphql_object_list', serialize($graphqllist));
        xarModVars::set('dynamicdata', 'restapi_token_storage', $storageType);
        xarModVars::set('dynamicdata', 'restapi_token_expires', intval($tokenExpires));
        xarModVars::set('dynamicdata', 'graphql_query_complexity', intval($queryComplexity));
        xarModVars::set('dynamicdata', 'graphql_query_depth', intval($queryDepth));
        xarModVars::set('dynamicdata', 'graphql_enable_timer', !empty($enableTimer) ? true : false);
        xarModVars::set('dynamicdata', 'graphql_trace_path', !empty($tracePath) ? true : false);
        xarModVars::set('dynamicdata', 'graphql_enable_cache', !empty($enableCache) ? true : false);
        xarModVars::set('dynamicdata', 'graphql_cache_plan', !empty($cachePlan) ? true : false);
        xarModVars::set('dynamicdata', 'graphql_cache_data', !empty($cacheData) ? true : false);
        xarModVars::set('dynamicdata', 'graphql_cache_operation', !empty($cacheOperation) ? true : false);
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
        $storageType = xarModVars::get('dynamicdata', 'restapi_token_storage');
        $tokenExpires = xarModVars::get('dynamicdata', 'restapi_token_expires');
        $queryComplexity = xarModVars::get('dynamicdata', 'graphql_query_complexity');
        $queryDepth = xarModVars::get('dynamicdata', 'graphql_query_depth');
        $enableTimer = xarModVars::get('dynamicdata', 'graphql_enable_timer');
        $tracePath = xarModVars::get('dynamicdata', 'graphql_trace_path');
        $enableCache = xarModVars::get('dynamicdata', 'graphql_enable_cache');
        $cachePlan = xarModVars::get('dynamicdata', 'graphql_cache_plan');
        $cacheData = xarModVars::get('dynamicdata', 'graphql_cache_data');
        $cacheOperation = xarModVars::get('dynamicdata', 'graphql_cache_operation');
    }

    DataObjectRESTBuilder::init();
    if (!xarVar::fetch('create_rst', 'notempty', $create_rst, 0, xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!empty($create_rst)) {
        DataObjectRESTBuilder::create_openapi($restapilist, $storageType, $tokenExpires, $enableTimer, $enableCache);
        xarController::redirect(xarServer::getCurrentURL(['create_rst' => null]));
        return true;
    }
    if (!xarVar::fetch('create_gql', 'notempty', $create_gql, 0, xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!empty($create_gql)) {
        sys::autoload();
        sys::import('modules.dynamicdata.class.graphql');
        $extraTypes = xarGraphQL::find_extra_types($graphqllist);
        xarGraphQL::dump_schema($extraTypes, $storageType, $tokenExpires, $queryComplexity, $queryDepth, $enableTimer, $tracePath, $enableCache, $cachePlan, $cacheData, $cacheOperation);
        xarController::redirect(xarServer::getCurrentURL(['create_gql' => null]));
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

    $data['tokenstorage'] = $storageType;
    $data['storagetypes'] = [
        'apcu' => [
            'name'    => 'apcu',
            'label'   => 'APC User Cache (APCu)',
            'enabled' => function_exists('apcu_fetch') ? true : false,
        ],
        'database' => [
            'name'    => 'database',
            'label'   => 'Database',
            'enabled' => true,
        ],
        'filesystem' => [
            'name'    => 'filesystem',
            'label'   => 'Filesystem',
            'enabled' => false,
        ],
    ];
    $data['tokenexpires'] = sprintf('%02d:%02d:%02d', floor($tokenExpires / 3600), intval($tokenExpires % 3600) / 60, intval($tokenExpires % 60));
    $data['querycomplexity'] = $queryComplexity;
    $data['querydepth'] = $queryDepth;
    $data['enabletimer'] = $enableTimer;
    $data['tracepath'] = $tracePath;
    $data['enablecache'] = $enableCache;
    $data['cacheplan'] = $cachePlan;
    $data['cachedata'] = $cacheData;
    $data['cacheoperation'] = $cacheOperation;

    xarTpl::setPageTemplateName('admin');

    return $data;
}
