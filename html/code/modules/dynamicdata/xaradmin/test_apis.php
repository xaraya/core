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
function dynamicdata_admin_test_apis(array $args=array())
{
    // Security
    if (!xarSecurity::check('EditDynamicData')) {
        return;
    }

    extract($args);

    xarVar::fetch('restapi', 'array', $restapi, array(), xarVar::NOT_REQUIRED);
    xarVar::fetch('graphql', 'array', $graphql, array(), xarVar::NOT_REQUIRED);
    xarVar::fetch('object_new','isset', $object_new, '', xarVar::NOT_REQUIRED);
    if (!empty($object_new)) {
        xarVar::fetch('restapi_new','isset', $restapi_new, '', xarVar::NOT_REQUIRED);
        if (!empty($restapi_new)) {
            $restapi[$object_new] = 'on';
        }
        xarVar::fetch('graphql_new','isset', $graphql_new, '', xarVar::NOT_REQUIRED);
        if (!empty($graphql_new)) {
            $graphql[$object_new] = 'on';
        }
    }
    $restapilist = array();
    $graphqllist = array();
    if (!empty($restapi) && !empty($graphql) && xarSec::confirmAuthKey()) {
        $restapilist = array_keys($restapi);
        xarModVars::set('dynamicdata', 'restapi_object_list', serialize($restapilist));
        $graphqllist = array_keys($graphql);
        xarModVars::set('dynamicdata', 'graphql_object_list', serialize($graphqllist));
    } else {
        $restapiserial = xarModVars::get('dynamicdata', 'restapi_object_list');
        if (!empty($restapiserial)) {
            $restapilist = unserialize($restapiserial);
        }
        $graphqllist = array();
        $graphqlserial = xarModVars::get('dynamicdata', 'graphql_object_list');
        if (!empty($graphqlserial)) {
            $graphqllist = unserialize($graphqlserial);
        }
    }

    DataObjectRESTBuilder::init();
    if (!xarVar::fetch('create_rst', 'notempty', $create_rst, 0, xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!empty($create_rst)) {
        DataObjectRESTBuilder::create_openapi($restapilist);
        xarController::redirect(xarServer::getCurrentURL(array('create_rst'=> null)));
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
        xarGraphQL::dump_schema($extraTypes);
        xarController::redirect(xarServer::getCurrentURL(array('create_gql'=> null)));
        return true;
    }

    $data = array();
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
    $known_objects = array();
    foreach ($data['objects'] as $item) {
        array_push($known_objects, $item['name']);
    }
    $objectlist = DataObjectMaster::getObjectList(array('name' => 'objects', 'fieldlist' => ['name', 'label']));
    $all_objects = $objectlist->getItems();
    $data['otherlist'] = array();
    foreach ($all_objects as $item) {
        if (!in_array($item['name'], $known_objects)) {
            array_push($data['otherlist'], $item);
        }
    }

    xarTpl::setPageTemplateName('admin');

    return $data;
}
