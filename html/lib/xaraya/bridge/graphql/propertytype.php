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

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * GraphQL ObjectType and query fields for "properties" dynamicdata object type
 */
class xarGraphQLPropertyType extends xarGraphQLBaseType
{
    public static string $_xar_name   = 'Property';
    public static string $_xar_type   = 'property';
    public static string $_xar_object = 'properties';
    /** @var array<mixed> */
    public static $_xar_queries = [
        'list' => 'properties',
        'item' => 'property',
    ];
    /** @var array<mixed> */
    public static $_xar_mutations = [];

    /**
     * This method *should* be overridden for each specific object type
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function _xar_get_object_fields($object): array
    {
        $fields = [
            'id' => Type::nonNull(Type::id()),
            //'keys' => Type::listOf(Type::string()),
            'keys' => [
                'type' => Type::listOf(Type::string()),
                'resolve' => function ($property, $args, $context, ResolveInfo $info) {
                    xarGraphQL::tracePath(array_merge($info->path, ["property keys", gettype($property)]));
                    //print_r("property keys resolve");
                    if (is_array($property)) {
                        return array_keys($property);
                    }
                    if (!property_exists($property, 'keys')) {
                        //print_r("set property keys for " . $property->name);
                        $property->keys = array_filter(array_keys($property->descriptor->getArgs()), function ($k) {
                            return !str_starts_with($k, 'object_');
                        });
                    }
                    return $property->keys;
                },
            ],
            // @checkme name is not returned by getProperties() because it's DISPLAYONLY?
            'name' => Type::string(),
            'label' => Type::string(),
            '_objectid' => Type::string(),
            //'objectid' => xarGraphQLTypes::getType('object'),
            //'object_id' => static::_xar_get_deferred_field('object_id', 'object'),
            'type' => Type::string(),
            'defaultvalue' => Type::string(),
            'source' => Type::string(),
            'status' => Type::int(),
            'translatable' => Type::boolean(),
            'seq' => Type::int(),
            'configuration' => xarGraphQLTypes::getType('serial'),
            'configuration_kv' => [
                //'type' => Type::listOf(xarGraphQLTypes::getType("keyval")),
                'type' => xarGraphQLTypes::getTypeList("keyval"),
                'resolve' => function ($property, $args, $context, ResolveInfo $info) {
                    xarGraphQL::tracePath(array_merge($info->path, ["property configuration_kv"]));
                    if (is_array($property) && isset($property['configuration'])) {
                        $values = @unserialize($property['configuration']);
                        if (empty($values)) {
                            return [];
                        }
                        if (!is_array($values)) {
                            $values = ['' => $values];
                        }
                        $config = [];
                        foreach ($values as $key => $value) {
                            //if (is_array($value)) {
                            //    $value = json_encode($value);
                            //}
                            $config[] = ['key' => $key, 'value' => $value];
                        }
                        return $config;
                    }
                    if (is_object($property) && property_exists($property, 'configuration') && isset($property->configuration)) {
                        $values = @unserialize($property->configuration);
                        if (empty($values)) {
                            return [];
                        }
                        if (!is_array($values)) {
                            $values = ['' => $values];
                        }
                        $config = [];
                        foreach ($values as $key => $value) {
                            //if (is_array($value)) {
                            //    $value = json_encode($value);
                            //}
                            $config[] = ['key' => $key, 'value' => $value];
                        }
                        return $config;
                    }
                    return null;
                },
            ],
            //'objectref' => xarGraphQLTypes::getType("object"),
            //'args' => Type::listOf(Type::string()),
        ];
        return $fields;
    }

    /**
     * This method *should* be overridden for each specific object type
     */
    public static function _xar_get_input_fields($object, &$newType): array
    {
        // return static::_xar_get_object_fields($object);
        $fields = [
            'id' => Type::id(),  // allow null for create here
            'name' => Type::string(),
        ];
        return $fields;
    }
}
