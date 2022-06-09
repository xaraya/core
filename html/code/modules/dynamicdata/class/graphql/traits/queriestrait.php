<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Trait to handle default query fields for dataobjects (page, list, item)
 */
trait xarGraphQLQueriesTrait
{
    use xarGraphQLQueryPageTrait;
    use xarGraphQLQueryListTrait;
    use xarGraphQLQueryItemTrait;

    public static $_xar_type   = '';  // specify in the class using this trait
    public static $_xar_object = '';  // specify in the class using this trait
    public static $_xar_page   = '';  // specify in the class using this trait
    public static $_xar_list   = '';  // specify in the class using this trait
    public static $_xar_item   = '';  // specify in the class using this trait
    public static $_xar_queries = [];  // specify in the class using this trait

    public static function _xar_get_query_fields()
    {
        $fields = [];
        foreach (static::$_xar_queries as $name) {
            $fields[] = static::_xar_get_query_field($name);
        }
        return $fields;
    }

    /**
     * This method will be inherited by all specific object types, so it's important to use "static"
     * instead of "self" here - see https://www.php.net/manual/en/language.oop5.late-static-bindings.php
     */
    public static function _xar_get_query_field($name)
    {
        if (!empty(static::$_xar_page) && $name == static::$_xar_page) {
            return static::_xar_get_page_query(static::$_xar_page, static::$_xar_type, static::$_xar_object);
        }
        if (!empty(static::$_xar_list) && $name == static::$_xar_list) {
            return static::_xar_get_list_query(static::$_xar_list, static::$_xar_type, static::$_xar_object);
        }
        if (!empty(static::$_xar_item) && $name == static::$_xar_item) {
            return static::_xar_get_item_query(static::$_xar_item, static::$_xar_type, static::$_xar_object);
        }
    }

    /**
     * Add to the query resolver for the object type (page, list, item) - when using BuildSchema
     */
    public static function _xar_query_field_resolver($typename = 'query')
    {
        // call either list_query_resolver or item_query_resolver here depending on $args['id']
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) {
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["object query", $args]);
            }
            $name = strtolower($info->fieldName);
            $page_ext = '_page';
            if (substr($name, -strlen($page_ext)) === $page_ext) {
                $type = substr($name, 0, strlen($name) - strlen($page_ext));
                // @checkme do we want to use singular type here?
                $type = xarGraphQLInflector::singularize($type);
                $page_resolver = static::_xar_page_query_resolver($type);
                return call_user_func($page_resolver, $rootValue, $args, $context, $info);
            }
            $type = xarGraphQLInflector::singularize($name);
            if (!empty($args['id'])) {
                //print_r($info->parentType->name . "." . $info->fieldName . "[" . $args['id'] . "]");
                $item_resolver = static::_xar_item_query_resolver($type);
                return call_user_func($item_resolver, $rootValue, $args, $context, $info);
            }
            //print_r($info->parentType->name . "." . $info->fieldName);
            $list_resolver = static::_xar_list_query_resolver($type);
            return call_user_func($list_resolver, $rootValue, $args, $context, $info);
        };
        return $resolver;
    }
}
