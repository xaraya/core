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

use Xaraya\Bridge\GraphQL\GraphQLBuilder;
use Xaraya\Bridge\RestAPI\RestAPIBuilder;
use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminGui;
use sys;
use Exception;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata admin test_apis function
 * @extends MethodClass<AdminGui>
 */
class TestApisMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Test APIs
     * @uses \sys::autoload()
     * @see AdminGui::testApis()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('EditDynamicData')) {
            return;
        }

        extract($args);

        $this->var()->check('tab', $tab);
        if (!empty($tab) && in_array($tab, ['swagger-ui', 'datatables', 'playground'])) {
            $testDir = dirname(__DIR__) . '/xartests/';
            $testFile = $testDir . $tab . '.html';
            if (file_exists($testFile)) {
                $contents = file_get_contents($testFile);
                if (strpos($this->ctl()->getCurrentURL(), '/dynamicdata/admin/test_apis') !== false) {
                    // using index.php/dynamicdata/admin/test_apis or similar
                    $contents = str_replace('../../../../', '../../../', $contents);
                } else {
                    // using index.php?module=dynamicdata&type=admin&func=test_apis
                    $contents = str_replace('../../../../', './', $contents);
                }
                // use 'passthru' page template to output the contents as is here
                $this->tpl()->setPageTemplateName('passthru');
                return $contents;
            }
        }
        if (!empty($tab) && in_array($tab, ['openapi.json', 'schema.graphql'])) {
            $apiDir = sys::varpath() . '/cache/api/';
            $apiFile = $apiDir . $tab;
            if (file_exists($apiFile)) {
                //$contents = file_get_contents($apiFile);
                // use 'passthru' page template to output the contents as is here
                //$this->tpl()->setPageTemplateName('passthru');
                //return $contents;
                // see session-less page caching
                //sys::import('xaraya.caching.output.page');
                $cacheCode = md5($this->ctl()->getServerVar('HTTP_HOST') . $this->ctl()->getServerVar('REQUEST_URI'));
                //xarPageCache::$cacheCode = $cacheCode;
                $modtime = filemtime($apiFile);
                //xarPageCache::sendHeaders($modtime);
                $etag = $cacheCode . $modtime;
                $match = $this->ctl()->getServerVar('HTTP_IF_NONE_MATCH') ?? null;
                if (!empty($match) && $match == $etag) {
                    header('HTTP/1.1 304 Not Modified');
                    header("Cache-Control: public, must-revalidate");
                    $this->exit();
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
                $this->exit();
            }
        }
        $this->var()->check('restapi', $restapi, 'array', []);
        $this->var()->check('graphql', $graphql, 'array', []);
        $this->var()->check('object_new', $object_new, 'isset', '');
        if (!empty($object_new)) {
            $this->var()->check('restapi_new', $restapi_new, 'isset', '');
            if (!empty($restapi_new)) {
                $restapi[$object_new] = 'on';
            }
            $this->var()->check('graphql_new', $graphql_new, 'isset', '');
            if (!empty($graphql_new)) {
                $graphql[$object_new] = 'on';
            }
        }
        $this->var()->check('module_new', $module_new, 'isset', '');
        if (!empty($module_new)) {
            $this->var()->check('restapi_module', $restapi_module, 'isset', '');
            if (!empty($restapi_module)) {
                $restapi[$module_new] = 'on';
            }
            $this->var()->check('graphql_module', $graphql_module, 'isset', '');
            if (!empty($graphql_module)) {
                $graphql[$module_new] = 'on';
            }
        }
        $this->var()->check('tokenstorage', $storageType, 'isset', 'database');
        $this->var()->check('tokenexpires', $tokenExpires, 'isset', '12:00:00');
        if (!empty($tokenExpires)) {
            [$hour, $min, $sec] = explode(':', $tokenExpires);
            $tokenExpires = (((intval($hour) * 60) + intval($min)) * 60) + intval($sec);
        } else {
            $tokenExpires = 12 * 60 * 60;  // 12 hours
        }
        $this->var()->check('querycomplexity', $queryComplexity, 'isset', 0);
        $this->var()->check('querydepth', $queryDepth, 'isset', 0);
        $this->var()->check('enabletimer', $enableTimer, 'isset', false);
        $this->var()->check('tracepath', $tracePath, 'isset', false);
        $this->var()->check('enablecache', $enableCache, 'isset', false);
        $this->var()->check('cacheplan', $cachePlan, 'isset', false);
        $this->var()->check('cachedata', $cacheData, 'isset', false);
        $this->var()->check('cacheoperation', $cacheOperation, 'isset', false);
        $restapilist = [];
        $graphqllist = [];
        if (!empty($restapi) && !empty($graphql) && $this->sec()->confirmAuthKey()) {
            $restapilist = array_keys($restapi);
            $this->mod()->setVar('restapi_object_list', serialize($restapilist));
            $graphqllist = array_keys($graphql);
            $this->mod()->setVar('graphql_object_list', serialize($graphqllist));
            $this->mod()->setVar('restapi_token_storage', $storageType);
            $this->mod()->setVar('restapi_token_expires', intval($tokenExpires));
            $this->mod()->setVar('graphql_query_complexity', intval($queryComplexity));
            $this->mod()->setVar('graphql_query_depth', intval($queryDepth));
            $this->mod()->setVar('graphql_enable_timer', !empty($enableTimer) ? true : false);
            $this->mod()->setVar('graphql_trace_path', !empty($tracePath) ? true : false);
            $this->mod()->setVar('graphql_enable_cache', !empty($enableCache) ? true : false);
            $this->mod()->setVar('graphql_cache_plan', !empty($cachePlan) ? true : false);
            $this->mod()->setVar('graphql_cache_data', !empty($cacheData) ? true : false);
            $this->mod()->setVar('graphql_cache_operation', !empty($cacheOperation) ? true : false);
            // save to cache if enabled
            $this->clearCacheFiles();
            $this->mod()->cacheVars(__METHOD__);
        } else {
            $restapiserial = $this->mod()->getVar('restapi_object_list');
            if (!empty($restapiserial)) {
                $restapilist = unserialize($restapiserial);
            }
            $graphqllist = [];
            $graphqlserial = $this->mod()->getVar('graphql_object_list');
            if (!empty($graphqlserial)) {
                $graphqllist = unserialize($graphqlserial);
            }
            $storageType = $this->mod()->getVar('restapi_token_storage');
            $tokenExpires = $this->mod()->getVar('restapi_token_expires');
            $queryComplexity = $this->mod()->getVar('graphql_query_complexity');
            $queryDepth = $this->mod()->getVar('graphql_query_depth');
            $enableTimer = $this->mod()->getVar('graphql_enable_timer');
            $tracePath = $this->mod()->getVar('graphql_trace_path');
            $enableCache = $this->mod()->getVar('graphql_enable_cache');
            $cachePlan = $this->mod()->getVar('graphql_cache_plan');
            $cacheData = $this->mod()->getVar('graphql_cache_data');
            $cacheOperation = $this->mod()->getVar('graphql_cache_operation');
        }
        sys::import('xaraya.bridge.restapi.builder');

        RestAPIBuilder::init();
        $this->var()->check('create_rst', $create_rst, 'notempty', 0);
        if (!empty($create_rst)) {
            RestAPIBuilder::create_openapi($restapilist, $storageType, $tokenExpires, $enableTimer, $enableCache);
            $this->clearCacheFiles();
            $this->ctl()->redirect($this->ctl()->getCurrentURL(['create_rst' => null]));
            return true;
        }
        $this->var()->check('create_gql', $create_gql, 'notempty', 0);
        if (!empty($create_gql)) {
            sys::autoload();
            sys::import('xaraya.bridge.graphql.builder');
            $graphQLBuilder = new GraphQLBuilder();
            $graphQLBuilder->dumpSchema($graphqllist, $storageType, $tokenExpires, $queryComplexity, $queryDepth, $enableTimer, $tracePath, $enableCache, $cachePlan, $cacheData, $cacheOperation);
            $this->clearCacheFiles();
            $this->ctl()->redirect($this->ctl()->getCurrentURL(['create_gql' => null]));
            return true;
        }

        $data = [];
        $data['filemtimes'] = [];
        $openapi = sys::varpath() . '/cache/api/openapi.json';
        if (file_exists($openapi)) {
            $data['openapi'] = $openapi;
            $data['filemtimes']['openapi'] = filemtime($openapi);
        }
        $schema = sys::varpath() . '/cache/api/schema.graphql';
        if (file_exists($schema)) {
            $data['schema'] = $schema;
            $data['filemtimes']['schema'] = filemtime($schema);
        }
        $data['restapilist'] = $restapilist;
        $data['graphqllist'] = $graphqllist;
        $mergedlist = array_unique(array_merge($restapilist, $graphqllist));
        $data['objects'] = RestAPIBuilder::get_potential_objects($mergedlist);
        $known_objects = [];
        foreach ($data['objects'] as $item) {
            array_push($known_objects, $item['name']);
        }
        $objectlist = $this->data()->getObjectList(['name' => 'objects', 'fieldlist' => ['name', 'label']]);
        $all_objects = $objectlist->getItems();
        $data['otherlist'] = [];
        foreach ($all_objects as $item) {
            if (!in_array($item['name'], $known_objects)) {
                array_push($data['otherlist'], $item);
            }
        }
        $data['modules'] = RestAPIBuilder::get_potential_modules($mergedlist);
        $all_modules = $this->mod()->apiFunc('modules', 'admin', 'getitems');
        $data['othermodules'] = [];
        foreach ($all_modules as $item) {
            if (!array_key_exists($item['name'], $data['modules'])) {
                try {
                    $apiList = $this->mod()->apiFunc($item['name'], 'rest', 'getlist');
                    $item['displayname'] .= ' [' . count($apiList) . ']';
                } catch (Exception) {
                    $apiList = RestAPIBuilder::find_default_api_functions($item['name']);
                    if (empty($apiList)) {
                        continue;
                    }
                    $item['displayname'] .= ' (' . count($apiList) . ')';
                }
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

        $this->tpl()->setPageTemplateName('admin');

        return $data;
    }

    /**
     * Summary of clearCacheFiles
     * @return void
     * @see \Xaraya\Routing\FastRouter::FASTROUTE_CACHE_FILE
     * @see \Xaraya\Bridge\Middleware\RoutingHandler::COMBINED_CACHE_FILE
     * @see \Xaraya\Routing\Routing::MATCHER_CACHE_FILE
     * @see \Xaraya\Bridge\Routing\RoutingBridge::ROUTING_CACHE_FILE
     */
    protected function clearCacheFiles()
    {
        $cacheFile = sys::varpath() . '/cache/url_fastroute_cache.php';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        $cacheFile = sys::varpath() . '/cache/url_combined_cache.php';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        $cacheFile = sys::varpath() . '/cache/url_matching_routes.php';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        $cacheFile = sys::varpath() . '/cache/url_generating_routes.php';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        $cacheFile = sys::varpath() . '/cache/api/routing_cache.php';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        $cacheFile = sys::varpath() . '/cache/api/url_matching_routes.php';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        $cacheFile = sys::varpath() . '/cache/api/url_generating_routes.php';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
}
