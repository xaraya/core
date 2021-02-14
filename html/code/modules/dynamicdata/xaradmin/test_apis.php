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

    if (!xarVar::fetch('create', 'notempty', $create, 0, xarVar::NOT_REQUIRED)) {
        return;
    }
    DataObjectRESTBuilder::init();
    if (!empty($create)) {
        DataObjectRESTBuilder::create_openapi();
    }
    /**
    if (!xarVar::fetch('create_gql', 'notempty', $create_gql, 0, xarVar::NOT_REQUIRED)) {
        return;
    }
    GraphQL::init();
    if (!empty($create_gql)) {
        GraphQL::dump_schema();
    }
     */

    $data = array();
    $openapi = sys::varpath() . '/cache/openapi.json';
    if (file_exists($openapi)) {
        $data['openapi'] = $openapi;
    }
    $graphql = sys::varpath() . '/cache/schema.graphql';
    if (file_exists($graphql)) {
        $data['graphql'] = $graphql;
    }
    $data['objects'] = DataObjectRESTBuilder::get_objects();

    xarTpl::setPageTemplateName('admin');

    return $data;
}
