<?php

/**
 * @package core\bridge
 * @subpackage graphql
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */

namespace Xaraya\Bridge\GraphQL\Types;

use Xaraya\Bridge\GraphQL\GraphQLHandler;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Exception;

/**
 * For documentation purposes only - available via QueriesTrait
 */
interface QueriesInterface extends QueryPageInterface, QueryListInterface, QueryItemInterface
{
    /**
     * Get the query fields listed in the $_xar_queries property of the actual class
     * @return array<mixed>
     */
    public static function get_query_fields(): array;
    /**
     * This method will be inherited by all specific object types, so it's important to use "static"
     * @param mixed $name
     * @param mixed $kind
     * @return array<string, mixed>
     */
    public static function get_query_field($name, $kind = ''): array;
    /**
     * Add to the query resolver for the object type (page, list, item) - when using BuildSchema
     * @param mixed $typename
     * @return callable
     */
    public static function query_field_resolver($typename = 'query'): callable;
}

/**
 * Trait to handle default query fields for dataobjects (page, list, item)
 */
trait QueriesTrait
{
    use QueryPageTrait;
    use QueryListTrait;
    use QueryItemTrait;

    public static string $_xar_type   = '';  // specify in the class using this trait
    public static string $_xar_object = '';  // specify in the class using this trait
    /** @var array<mixed> */
    public static $_xar_queries = [];  // specify in the class using this trait

    /**
     * Get the query fields listed in the $_xar_queries property of the actual class
     * @return array<mixed>
     */
    public static function get_query_fields(): array
    {
        $fields = [];
        foreach (static::$_xar_queries as $kind => $name) {
            if (!empty($name)) {
                $fields[] = static::get_query_field($name, $kind);
            }
        }
        return $fields;
    }

    /**
     * This method will be inherited by all specific object types, so it's important to use "static"
     * instead of "self" here - see https://www.php.net/manual/en/language.oop5.late-static-bindings.php
     * @param mixed $name
     * @param mixed $kind
     * @throws \Exception
     * @return array<string, mixed>
     */
    public static function get_query_field($name, $kind = ''): array
    {
        if (empty($kind) || is_numeric($kind)) {
            $lname = strtolower($name);
            $ext = '_page';
            if (str_ends_with($lname, $ext)) {
                $kind = 'page';
            } elseif ($lname === static::$_xar_object) {
                $kind = 'list';
            } elseif ($lname === static::$_xar_type) {
                $kind = 'item';
            }
        }
        // allow overriding page/list/item query resolvers for custom type classes using this trait
        return match ($kind) {
            'page' => static::get_page_query($name, static::$_xar_type, static::$_xar_object),
            'list' => static::get_list_query($name, static::$_xar_type, static::$_xar_object),
            'item' => static::get_item_query($name, static::$_xar_type, static::$_xar_object),
            default => throw new Exception("Unknown '$kind' query '$name'"),
        };
    }

    /**
     * Add to the query resolver for the object type (page, list, item) - when using BuildSchema
     * @param mixed $typename
     * @return callable
     */
    public static function query_field_resolver($typename = 'query'): callable
    {
        // call either list_query_resolver or item_query_resolver here depending on $args['id']
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) {
            $context->tracePath(__CLASS__ . '::query_field_resolver: query', $info->path);
            // @todo check if type class corresponding to fieldname has overridden *_query_resolver
            $name = strtolower($info->fieldName);
            $page_ext = '_page';
            if (str_ends_with($name, $page_ext)) {
                $type = substr($name, 0, strlen($name) - strlen($page_ext));
                // @checkme do we want to use singular type here?
                $type = GraphQLInflector::singularize($type);
                $page_resolver = static::page_query_resolver($type);
                return call_user_func($page_resolver, $rootValue, $args, $context, $info);
            }
            $type = GraphQLInflector::singularize($name);
            if (!empty($args['id'])) {
                //print_r($info->parentType->name . "." . $info->fieldName . "[" . $args['id'] . "]");
                $item_resolver = static::item_query_resolver($type);
                return call_user_func($item_resolver, $rootValue, $args, $context, $info);
            }
            //print_r($info->parentType->name . "." . $info->fieldName);
            $list_resolver = static::list_query_resolver($type);
            return call_user_func($list_resolver, $rootValue, $args, $context, $info);
        };
        return $resolver;
    }
}

class Queries implements QueriesInterface
{
    use QueriesTrait;
}
